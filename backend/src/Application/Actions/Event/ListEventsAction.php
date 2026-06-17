<?php

declare(strict_types=1);

namespace App\Application\Actions\Event;

use App\Application\Response\ApiResponse;
use App\Domain\Event\EventService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListEventsAction
{
    public function __construct(
        private EventService $eventService,
        private ApiResponse $response,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $events = $this->eventService->listPublishedEvents();

        return $this->response->json($response, [
            'data' => $events,
        ]);
    }
}
