<?php

namespace App\Notifications;

use Ifresh\FakkelLaravel\Notifications\Message;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification as SpatieBackupHasFailedNotification;

class BackupHasFailedNotification extends SpatieBackupHasFailedNotification
{
    protected array $payload = [];

    public function toFakkel(): Message
    {
        $this->payload = [
            'application' => $this->applicationName(),
        ];

        $this->backupDestinationProperties()->each(function ($value, $name) {
            $this->payload[$name] = $value;
        });

        $this->payload['message'] = $this->event->exception->getMessage();
        $this->payload['trace'] = $this->event->exception->getTraceAsString();

        return (new Message())
            ->setMessage(trans('backup::notifications.backup_failed_subject', ['application_name' => $this->applicationName()]))
            ->setTags(['backup', 'failed'])
            ->setPayload($this->payload);
    }
}
