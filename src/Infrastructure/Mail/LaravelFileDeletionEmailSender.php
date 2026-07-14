<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Domain\File\Contract\FileDeletionEmailSender;
use App\Domain\File\Entity\FileDeletionNotification;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;

final readonly class LaravelFileDeletionEmailSender implements FileDeletionEmailSender
{
    public function __construct(private Mailer $mailer) {}

    public function send(FileDeletionNotification $notification): void
    {
        $body = implode(PHP_EOL, [
            'A stored file has been deleted.',
            '',
            sprintf('File: %s', $notification->originalName),
            sprintf('File ID: %s', $notification->fileId),
            sprintf('Reason: %s', $notification->reason->value),
            sprintf('Deleted at: %s', $notification->deletedAt->format(DATE_ATOM)),
        ]);

        $this->mailer->raw(
            $body,
            static function (Message $message) use ($notification): void {
                $message
                    ->to($notification->recipient)
                    ->subject(sprintf('File deleted: %s', $notification->originalName));
            },
        );
    }
}
