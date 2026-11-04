<?php

declare(strict_types=1);

namespace FlowForge\Tests;

use FlowForge\Core\Request;

class RequestTest
{
    public static function run(): void
    {
        echo "Running RequestTest...\n";

        // 1. GET query
        $req1 = new Request('GET', '/search?term=hackathon&page=2', ['term' => 'hackathon', 'page' => '2']);
        TestCase::assertEquals('GET', $req1->getMethod(), 'Normalizes GET method');
        TestCase::assertEquals('/search', $req1->getPath(), 'Extracts clean path without query string');
        TestCase::assertEquals('hackathon', $req1->getQuery('term'), 'Retrieves specific query param');
        TestCase::assertEquals('2', $req1->getQuery('page'), 'Retrieves second query param');

        // 2. POST form
        $req2 = new Request('POST', '/requests', [], ['title' => 'Sample Activity', 'venue' => 'Gym']);
        TestCase::assertEquals('POST', $req2->getMethod(), 'Normalizes POST method');
        TestCase::assertEquals('Sample Activity', $req2->getPost('title'), 'Retrieves POST form input');
        TestCase::assertEquals('Sample Activity', $req2->input('title'), 'input() retrieves POST value');

        // 3. JSON body
        $jsonPayload = json_encode(['title' => 'Robotics Meetup', 'event_date' => '2026-12-01']);
        $req3 = new Request(
            'POST',
            '/api/requests',
            [],
            [],
            ['Content-Type' => 'application/json'],
            $jsonPayload
        );
        TestCase::assertFalse($req3->hasJsonError(), 'Valid JSON has no JSON errors');
        TestCase::assertEquals('Robotics Meetup', $req3->json('title'), 'Parses valid JSON body parameter');
        TestCase::assertEquals('2026-12-01', $req3->input('event_date'), 'input() retrieves JSON field seamlessly');

        // 4. Malformed JSON
        $badJson = '{"title": "Unfinished JSON...';
        $req4 = new Request(
            'POST',
            '/api/requests',
            [],
            [],
            ['Content-Type' => 'application/json'],
            $badJson
        );
        TestCase::assertTrue($req4->hasJsonError(), 'Detects malformed JSON syntax');
        TestCase::assertNotNull($req4->getJsonErrorMessage(), 'Provides informative JSON error message');
    }
}
