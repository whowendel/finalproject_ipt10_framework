<?php

declare(strict_types=1);

namespace FlowForge\Tests;

use FlowForge\Support\Validator;

class ValidatorTest
{
    public static function run(): void
    {
        echo "Running ValidatorTest...\n";

        // 1. Missing required field
        $v1 = Validator::make([
            'title' => '',
            'venue' => 'Main Hall',
        ], [
            'title' => 'required',
            'venue' => 'required',
        ]);
        TestCase::assertTrue($v1->fails(), 'Fails when required field is empty');
        TestCase::assertNotNull($v1->first('title'), 'Produces field-specific error for missing title');

        // 2. Overlong text
        $v2 = Validator::make([
            'title' => str_repeat('A', 250),
        ], [
            'title' => 'required|max:200',
        ]);
        TestCase::assertTrue($v2->fails(), 'Fails when string exceeds max length');
        TestCase::assertContains('must not exceed 200 characters', $v2->first('title') ?? '', 'Error mentions max limit');

        // 3. Valid payload
        $v3 = Validator::make([
            'title' => 'AI Robotics Workshop',
            'description' => 'Hands-on programming session.',
            'event_date' => '2026-11-15',
            'venue' => 'Computer Lab 3',
        ], [
            'title' => 'required|max:200',
            'description' => 'required|max:2000',
            'event_date' => 'required|date',
            'venue' => 'required|max:200',
        ]);
        TestCase::assertTrue($v3->passes(), 'Passes with completely valid payload');
        TestCase::assertEquals([], $v3->errors(), 'Has empty errors array on pass');

        // 4. Invalid date or missing venue
        $v4 = Validator::make([
            'title' => 'Music Fest',
            'event_date' => 'not-a-valid-date',
            'venue' => null,
        ], [
            'title' => 'required',
            'event_date' => 'required|date',
            'venue' => 'required',
        ]);
        TestCase::assertTrue($v4->fails(), 'Fails on invalid date format');
        TestCase::assertNotNull($v4->first('event_date'), 'Flags invalid date format');
        TestCase::assertNotNull($v4->first('venue'), 'Flags missing venue');
    }
}
