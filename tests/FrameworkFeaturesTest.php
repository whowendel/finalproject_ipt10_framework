<?php

declare(strict_types=1);

namespace FlowForge\Tests;

use App\Services\HolidayService;
use FlowForge\Core\Config;
use FlowForge\Core\Env;
use FlowForge\Core\HttpException;
use FlowForge\Core\Pipeline;
use FlowForge\Core\Request;
use FlowForge\Core\Response;
use FlowForge\Core\Router;
use FlowForge\Core\Url;
use FlowForge\Http\HttpClient;
use FlowForge\Http\HttpResponse;
use FlowForge\Support\Cache;
use FlowForge\Support\Logger;

/**
 * Fake HTTP client: returns canned responses and counts calls, so the
 * holiday API tests never touch the network.
 */
final class FakeHttpClient extends HttpClient
{
    public int $calls = 0;
    public array $urls = [];

    public function __construct(private HttpResponse $response)
    {
        parent::__construct();
    }

    public function get(string $url, array $query = [], array $headers = []): HttpResponse
    {
        $this->calls++;
        $this->urls[] = $url;
        return $this->response;
    }
}

class FrameworkFeaturesTest
{
    public static function run(): void
    {
        echo "Running FrameworkFeaturesTest...\n";

        self::testEnvAndConfig();
        self::testUrlAndBasePath();
        self::testPipelineAndRouteMiddleware();
        self::testHolidayService();
    }

    private static function testEnvAndConfig(): void
    {
        $parsed = Env::parse(<<<ENV
            # comment line
            APP_NAME="Flow Forge"
            APP_DEBUG=true
            DB_PORT=3306   # inline comment
            export HOLIDAY_COUNTRY='PH'
            not a valid line
            ENV);

        TestCase::assertEquals('Flow Forge', $parsed['APP_NAME'] ?? null, 'Env parses double-quoted values');
        TestCase::assertEquals('3306', $parsed['DB_PORT'] ?? null, 'Env strips inline comments');
        TestCase::assertEquals('PH', $parsed['HOLIDAY_COUNTRY'] ?? null, 'Env supports export prefix and single quotes');
        TestCase::assertFalse(isset($parsed['not a valid line']), 'Env ignores malformed lines');

        Env::set(['FF_TEST_FLAG' => 'false', 'FF_TEST_EMPTY' => '']);
        TestCase::assertFalse(Env::get('FF_TEST_FLAG'), 'Env::get casts "false" to boolean');
        TestCase::assertEquals('fallback', Env::get('FF_TEST_EMPTY', 'fallback'), 'Env::get returns default for empty values');

        Config::set(['database' => ['driver' => 'sqlite', 'port' => 3306]]);
        TestCase::assertEquals('sqlite', Config::get('database.driver'), 'Config::get reads nested keys with dot notation');
        TestCase::assertEquals('x', Config::get('database.missing', 'x'), 'Config::get falls back to default');
        Config::set([]);
    }

    private static function testUrlAndBasePath(): void
    {
        TestCase::assertEquals('', Url::detectBasePath('/index.php', '/requests'), 'php -S -t public: no base path');
        TestCase::assertEquals(
            '/IPT10 Final Project/public',
            Url::detectBasePath('/IPT10 Final Project/public/index.php', '/IPT10 Final Project/public/requests/3'),
            'XAMPP: detects .../public sub-folder (with spaces)'
        );
        TestCase::assertEquals(
            '/IPT10 Final Project',
            Url::detectBasePath('/IPT10 Final Project/public/index.php', '/IPT10 Final Project/requests'),
            'XAMPP: detects project folder when root .htaccess rewrites into public/'
        );

        Url::setBasePath('/my-app');
        TestCase::assertEquals('/my-app/requests/5', Url::to('/requests/5'), 'Url::to prefixes the base path');
        TestCase::assertEquals('/my-app/assets/app.css', Url::asset('app.css'), 'Url::asset builds asset URLs');
        TestCase::assertEquals('https://example.com/x', Url::to('https://example.com/x'), 'Url::to leaves absolute URLs alone');
        TestCase::assertEquals('/my-app/login', Response::redirect('/login')->getHeaders()['Location'], 'Redirects include the base path');
        Url::setBasePath('');
        TestCase::assertEquals('/login', Response::redirect('/login')->getHeaders()['Location'], 'Redirects work at the domain root');
    }

    private static function testPipelineAndRouteMiddleware(): void
    {
        // Pipeline order: first middleware is the outermost layer.
        $trace = [];
        $pipeline = new Pipeline([
            function (Request $r, callable $next) use (&$trace) { $trace[] = 'A-in'; $res = $next($r); $trace[] = 'A-out'; return $res; },
            function (Request $r, callable $next) use (&$trace) { $trace[] = 'B-in'; $res = $next($r); $trace[] = 'B-out'; return $res; },
        ]);
        $pipeline->process(new Request('GET', '/'), function () use (&$trace) { $trace[] = 'handler'; return Response::html('ok'); });
        TestCase::assertEquals('A-in,B-in,handler,B-out,A-out', implode(',', $trace), 'Pipeline runs middleware in onion order');

        // Short-circuit: middleware can stop the request.
        $blocked = (new Pipeline([fn(Request $r, callable $next) => Response::html('blocked', 403)]))
            ->process(new Request('GET', '/'), fn() => Response::html('should not run'));
        TestCase::assertEquals(403, $blocked->getStatusCode(), 'Middleware can short-circuit with its own response');

        // Router groups, prefixes, aliases with parameters.
        $router = new Router();
        $router->aliasMiddleware('tag', fn(string $value) => function (Request $r, callable $next) use ($value) {
            $res = $next($r);
            return $res->setHeader('X-Tag', ($res->getHeaders()['X-Tag'] ?? '') . $value);
        });
        $router->group(['prefix' => '/admin', 'middleware' => ['tag:G']], function (Router $router) {
            $router->get('/dashboard', fn() => Response::html('dash'))->middleware('tag:R');
        });
        $router->get('/denied', fn() => Response::html('secret'))->middleware(
            fn(Request $r, callable $next) => throw new HttpException(403, 'nope')
        );

        $res = $router->dispatch(new Request('GET', '/admin/dashboard'));
        TestCase::assertEquals(200, $res->getStatusCode(), 'Group prefix is applied to routes');
        TestCase::assertEquals('RG', $res->getHeaders()['X-Tag'] ?? '', 'Group and route middleware both run (group outermost)');

        $threw = null;
        try {
            $router->dispatch(new Request('GET', '/denied'));
        } catch (HttpException $e) {
            $threw = $e->getStatusCode();
        }
        TestCase::assertEquals(403, $threw, 'Middleware can abort with an HttpException');

        $router->setFallback(fn(int $status) => Response::html("custom {$status}", $status));
        $res404 = $router->dispatch(new Request('GET', '/missing'));
        TestCase::assertEquals('custom 404', $res404->getContent(), 'Custom fallback renders 404 pages');
        $res405 = $router->dispatch(new Request('POST', '/admin/dashboard'));
        TestCase::assertEquals('GET', $res405->getHeaders()['Allow'] ?? '', 'Fallback 405 keeps the Allow header');
    }

    private static function testHolidayService(): void
    {
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'flowforge-test-cache-' . bin2hex(random_bytes(4));
        $logger = new Logger(null); // logging disabled in tests

        $payload = json_encode([
            ['date' => '2026-12-25', 'localName' => 'Araw ng Pasko', 'name' => 'Christmas Day'],
            ['date' => '2026-12-30', 'localName' => 'Araw ni Rizal', 'name' => 'Rizal Day'],
        ]);
        $http = new FakeHttpClient(new HttpResponse(200, (string)$payload));
        $service = new HolidayService($http, new Cache($cacheDir), 'https://api.test/v3', 'ph', 3600, $logger);

        $christmas = $service->check('2026-12-25');
        TestCase::assertTrue($christmas['ok'] && $christmas['is_holiday'], 'Detects a public holiday from the API response');
        TestCase::assertEquals('Christmas Day', $christmas['holiday']['name'] ?? null, 'Returns the holiday name');
        TestCase::assertEquals('https://api.test/v3/PublicHolidays/2026/PH', $http->urls[0] ?? '', 'Calls the Nager.Date endpoint with year and country');

        $normal = $service->check('2026-12-26');
        TestCase::assertTrue($normal['ok'] && !$normal['is_holiday'], 'Non-holiday dates are reported as free');
        TestCase::assertEquals(1, $http->calls, 'Second lookup for the same year is served from cache');

        TestCase::assertEquals(['2026-12-30' => 'Rizal Day'], $service->namesForDates(['2026-12-30', '2026-12-29']), 'namesForDates maps only holiday dates');

        $invalid = $service->check('2026-13-45');
        TestCase::assertFalse($invalid['ok'], 'Invalid dates are rejected without calling the API');

        // Failure handling: API down -> graceful error, cached briefly.
        $down = new FakeHttpClient(new HttpResponse(0, '', [], 'Connection timed out'));
        $failing = new HolidayService($down, new Cache($cacheDir . '-down'), 'https://api.test/v3', 'PH', 3600, $logger);
        $result = $failing->check('2027-01-01');
        TestCase::assertFalse($result['ok'], 'Network failure is reported, not thrown');
        TestCase::assertContains('Could not reach', (string)$result['error'], 'Failure message explains the problem');
        $failing->check('2027-01-02');
        TestCase::assertEquals(1, $down->calls, 'Failures are cached to avoid hammering the API');

        $limited = new FakeHttpClient(new HttpResponse(429, '', ['retry-after' => '60']));
        $rate = (new HolidayService($limited, new Cache($cacheDir . '-429'), 'https://api.test/v3', 'PH', 3600, $logger))->forYear(2027);
        TestCase::assertContains('rate limit', (string)$rate['error'], 'HTTP 429 is reported as rate limiting');

        foreach ([$cacheDir, $cacheDir . '-down', $cacheDir . '-429'] as $dir) {
            array_map('unlink', glob($dir . '/*') ?: []);
            @rmdir($dir);
        }
    }
}
