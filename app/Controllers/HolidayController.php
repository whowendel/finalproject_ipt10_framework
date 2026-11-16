<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\HolidayService;
use FlowForge\Core\Request;
use FlowForge\Core\Response;

/**
 * JSON endpoints that expose the external holiday API to the front end.
 *   GET /api/holidays?year=2026
 *   GET /api/holidays/check?date=2026-12-25
 */
class HolidayController
{
    public function __construct(private ?HolidayService $holidays = null)
    {
        $this->holidays ??= new HolidayService();
    }

    public function index(Request $request): Response
    {
        $year = (int)$request->getQuery('year', date('Y'));
        $result = $this->holidays->forYear($year);

        if (!$result['ok']) {
            return Response::json(['message' => $result['error']], 502);
        }

        return Response::json([
            'country' => $this->holidays->country(),
            'year' => $year,
            'data' => $result['holidays'],
        ]);
    }

    public function check(Request $request): Response
    {
        $date = trim((string)$request->getQuery('date', ''));
        $result = $this->holidays->check($date);

        if (!$result['ok']) {
            $status = str_starts_with((string)$result['error'], 'Invalid date') ? 422 : 502;
            return Response::json(['message' => $result['error']], $status);
        }

        return Response::json([
            'country' => $this->holidays->country(),
            'date' => $result['date'],
            'is_holiday' => $result['is_holiday'],
            'holiday' => $result['holiday'],
        ]);
    }
}
