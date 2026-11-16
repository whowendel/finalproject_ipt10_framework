<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\RequestRepository;
use App\Services\HolidayService;
use FlowForge\Core\Request;
use FlowForge\Core\Response;
use FlowForge\Core\View;

/**
 * Public, read-only "Events" page: the published side of the CMS.
 * Only requests that a reviewer approved are shown here.
 */
class EventController
{
    public function __construct(
        private ?RequestRepository $requests = null,
        private ?HolidayService $holidays = null
    ) {
        $this->requests ??= new RequestRepository();
        $this->holidays ??= new HolidayService();
    }

    public function index(Request $request): Response
    {
        $events = $this->requests->findApproved();
        $today = date('Y-m-d');

        $upcoming = array_values(array_filter($events, fn($e) => $e->event_date >= $today));
        $past = array_reverse(array_values(array_filter($events, fn($e) => $e->event_date < $today)));

        return Response::html(View::render('events/index', [
            'title' => 'Campus Events - FlowForge',
            'upcoming' => $upcoming,
            'past' => $past,
            'holidayNames' => $this->holidays->namesForDates(array_map(fn($e) => $e->event_date, $events)),
        ]));
    }
}
