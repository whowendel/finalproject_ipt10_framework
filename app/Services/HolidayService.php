<?php

declare(strict_types=1);

namespace App\Services;

use FlowForge\Core\Config;
use FlowForge\Http\HttpClient;
use FlowForge\Support\Cache;
use FlowForge\Support\Logger;

/**
 * External API integration: Nager.Date public holidays (https://date.nager.at).
 *
 * Endpoint:  GET {base_url}/PublicHolidays/{year}/{countryCode}
 * Response:  JSON array of {"date":"2026-12-25","localName":"Araw ng Pasko","name":"Christmas Day",...}
 * Auth:      none (free, public API, no key)
 *
 * Results are cached per country+year (24 h by default) so the API is called at
 * most once a day per year. If the API is down, the failure is cached for a few
 * minutes and the app keeps working with a "holiday check unavailable" notice.
 */
class HolidayService
{
    private const FAILURE_TTL = 300;

    private HttpClient $http;
    private Cache $cache;
    private Logger $logger;
    private string $baseUrl;
    private string $country;
    private int $ttl;

    public function __construct(
        ?HttpClient $http = null,
        ?Cache $cache = null,
        ?string $baseUrl = null,
        ?string $country = null,
        ?int $ttl = null,
        ?Logger $logger = null
    ) {
        $this->http = $http ?? new HttpClient((int)Config::get('holidays.timeout', 5));
        $this->cache = $cache ?? new Cache((string)Config::get('cache.path'));
        $this->baseUrl = rtrim($baseUrl ?? (string)Config::get('holidays.base_url', 'https://date.nager.at/api/v3'), '/');
        $this->country = strtoupper($country ?? (string)Config::get('holidays.country', 'PH'));
        $this->ttl = $ttl ?? (int)Config::get('holidays.cache_ttl', 86400);
        $this->logger = $logger ?? Logger::instance();
    }

    public function country(): string
    {
        return $this->country;
    }

    /**
     * All public holidays of a year.
     *
     * @return array{ok: bool, holidays: array<int, array{date: string, name: string, local_name: string}>, error: ?string}
     */
    public function forYear(int $year): array
    {
        if ($year < 1975 || $year > 2100) {
            return ['ok' => false, 'holidays' => [], 'error' => 'Year is outside the supported range.'];
        }

        $key = "holidays.{$this->country}.{$year}";
        $cached = $this->cache->get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $response = $this->http->get("{$this->baseUrl}/PublicHolidays/{$year}/{$this->country}");

        if ($response->ok() && is_array($response->json())) {
            $holidays = [];
            foreach ($response->json() as $item) {
                if (!is_array($item) || empty($item['date'])) {
                    continue;
                }
                $holidays[] = [
                    'date' => (string)$item['date'],
                    'name' => (string)($item['name'] ?? 'Public holiday'),
                    'local_name' => (string)($item['localName'] ?? ''),
                ];
            }

            $result = ['ok' => true, 'holidays' => $holidays, 'error' => null];
            $this->cache->put($key, $result, $this->ttl);
            return $result;
        }

        // Error handling: classify the failure, log it, and cache it briefly so
        // a broken API does not slow down every page (simple rate limiting).
        $error = match (true) {
            $response->status === 0 => 'Could not reach the holiday service.',
            $response->status === 404 => "Unknown country code \"{$this->country}\".",
            $response->status === 429 => 'Holiday service rate limit reached. Try again later.',
            default => "Holiday service returned HTTP {$response->status}.",
        };

        $retryAfter = (int)($response->header('retry-after') ?? 0);
        $this->logger->warning('Holiday API request failed', [
            'country' => $this->country,
            'year' => $year,
            'status' => $response->status,
            'error' => $response->error,
        ]);

        $result = ['ok' => false, 'holidays' => [], 'error' => $error];
        $this->cache->put($key, $result, max(self::FAILURE_TTL, $retryAfter));
        return $result;
    }

    /**
     * Check one date (YYYY-MM-DD).
     *
     * @return array{ok: bool, date: string, is_holiday: bool, holiday: ?array, error: ?string}
     */
    public function check(string $date): array
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            return ['ok' => false, 'date' => $date, 'is_holiday' => false, 'holiday' => null, 'error' => 'Invalid date format, expected YYYY-MM-DD.'];
        }

        $year = $this->forYear((int)$parsed->format('Y'));
        if (!$year['ok']) {
            return ['ok' => false, 'date' => $date, 'is_holiday' => false, 'holiday' => null, 'error' => $year['error']];
        }

        foreach ($year['holidays'] as $holiday) {
            if ($holiday['date'] === $date) {
                return ['ok' => true, 'date' => $date, 'is_holiday' => true, 'holiday' => $holiday, 'error' => null];
            }
        }

        return ['ok' => true, 'date' => $date, 'is_holiday' => false, 'holiday' => null, 'error' => null];
    }

    /**
     * Map of date => holiday name for the given dates (used to flag list rows).
     *
     * @param string[] $dates
     * @return array<string, string>
     */
    public function namesForDates(array $dates): array
    {
        $years = [];
        foreach ($dates as $date) {
            if (preg_match('/^(\d{4})-\d{2}-\d{2}$/', $date, $m)) {
                $years[(int)$m[1]] = true;
            }
        }

        $map = [];
        foreach (array_keys($years) as $year) {
            foreach ($this->forYear($year)['holidays'] as $holiday) {
                $map[$holiday['date']] = $holiday['name'];
            }
        }

        return array_intersect_key($map, array_flip($dates));
    }

    /**
     * Next public holidays from a given date (default: today).
     *
     * @return array<int, array{date: string, name: string, local_name: string}>
     */
    public function upcoming(int $limit = 5, ?string $from = null): array
    {
        $from ??= date('Y-m-d');
        $year = (int)substr($from, 0, 4);

        $list = array_merge($this->forYear($year)['holidays'], $this->forYear($year + 1)['holidays']);
        $list = array_values(array_filter($list, fn($h) => $h['date'] >= $from));

        return array_slice($list, 0, $limit);
    }
}
