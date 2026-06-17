<?php

declare(strict_types=1);

use App\Application\Actions\Event\ListEventsAction;

$app->get('/events', ListEventsAction::class);
