<?php

declare(strict_types=1);

namespace FlowForge\Tests;

use FlowForge\Core\Request;
use FlowForge\Core\Response;
use FlowForge\Core\Router;

class RouterTest
{
    public static function run(): void
    {
        echo "Running RouterTest...\n";
        $router = new Router();

        // 1. Static route
        $router->get('/health', function (Request $req) {
            return Response::json(['status' => 'ok']);
        });

        $req1 = new Request('GET', '/health');
        $res1 = $router->dispatch($req1);
        TestCase::assertEquals(200, $res1->getStatusCode(), 'Static route responds with 200');
        TestCase::assertContains('"status": "ok"', $res1->getContent(), 'Static route body contains json');

        // 2. Parameterized route
        $router->get('/requests/{id}', function (Request $req) {
            return Response::json(['id' => $req->param('id')]);
        });

        $req2 = new Request('GET', '/requests/42');
        $res2 = $router->dispatch($req2);
        TestCase::assertEquals(200, $res2->getStatusCode(), 'Parameterized route responds with 200');
        TestCase::assertContains('"id": "42"', $res2->getContent(), 'Parameterized route captures id parameter');

        // 3. URL Normalization (trailing slash should match)
        $req3 = new Request('GET', '/requests/99/');
        $res3 = $router->dispatch($req3);
        TestCase::assertEquals(200, $res3->getStatusCode(), 'URL normalization matches route with trailing slash');
        TestCase::assertContains('"id": "99"', $res3->getContent(), 'Captures parameter despite trailing slash');

        // 4. Wrong Method (405 Method Not Allowed)
        $req4 = new Request('POST', '/health');
        $res4 = $router->dispatch($req4);
        TestCase::assertEquals(405, $res4->getStatusCode(), 'Wrong HTTP method returns 405 Method Not Allowed');

        // 5. Unknown Path (404 Not Found)
        $req5 = new Request('GET', '/non-existent-endpoint');
        $res5 = $router->dispatch($req5);
        TestCase::assertEquals(404, $res5->getStatusCode(), 'Unknown path returns 404 Not Found');
    }
}
