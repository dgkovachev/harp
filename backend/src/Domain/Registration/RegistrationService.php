<?php

declare(strict_types=1);

namespace App\Domain\Registration;

use App\Domain\Notification\DomainEvent;
use App\Domain\Notification\EventTypes;
use App\Infrastructure\Queue\RedisStreamPublisher;

final class RegistrationService
{
    public function __construct(
        private WaitlistService $waitlistService,
        private RedisStreamPublisher $publisher,
    ) {
    }

    public function registerForEvent(string $eventId, string $userId): array
    {
        $status = 'CONFIRMED';
        $registrationId = 'generated-registration-id';

        $event = new DomainEvent(
            EventTypes::REGISTRATION_CONFIRMED,
            $eventId,
            $userId,
            $registrationId,
            gmdate('c')
        );

        $this->publisher->publish($event);

        return [
            'registration_id' => $registrationId,
            'event_id' => $eventId,
            'user_id' => $userId,
            'status' => $status,
        ];
    }
}
