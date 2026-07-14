<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\RabbitMq;

use App\Domain\File\Entity\FileDeletionNotification;
use App\Domain\File\ValueObject\DeletionReason;
use App\Infrastructure\Messaging\RabbitMq\Exception\InvalidRabbitMqMessage;
use DateTimeImmutable;
use Throwable;

final readonly class FileDeletionNotificationDeserializer
{
    public function deserialize(string $messageBody): FileDeletionNotification
    {
        try {
            $payload = json_decode($messageBody, true, flags: JSON_THROW_ON_ERROR);

            if (! is_array($payload) || ($payload['event_name'] ?? null) !== FileDeletionNotificationSerializer::EVENT_NAME) {
                throw InvalidRabbitMqMessage::becausePayloadIsInvalid();
            }

            $data = $payload['data'] ?? null;

            if (! is_array($data)) {
                throw InvalidRabbitMqMessage::becausePayloadIsInvalid();
            }

            foreach (['recipient', 'file_id', 'original_name', 'reason', 'deleted_at'] as $requiredField) {
                if (! isset($data[$requiredField]) || ! is_string($data[$requiredField])) {
                    throw InvalidRabbitMqMessage::becausePayloadIsInvalid();
                }
            }

            $reason = DeletionReason::tryFrom($data['reason'])
                ?? throw InvalidRabbitMqMessage::becausePayloadIsInvalid();

            return new FileDeletionNotification(
                recipient: $data['recipient'],
                fileId: $data['file_id'],
                originalName: $data['original_name'],
                reason: $reason,
                deletedAt: new DateTimeImmutable($data['deleted_at']),
            );
        } catch (InvalidRabbitMqMessage $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw InvalidRabbitMqMessage::becausePayloadIsInvalid($exception);
        }
    }
}
