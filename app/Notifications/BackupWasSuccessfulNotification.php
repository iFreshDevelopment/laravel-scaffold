<?php

namespace App\Notifications;

use Ifresh\FakkelLaravel\Notifications\Message;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification as SpatieBackupWasSuccessfulNotification;

class BackupWasSuccessfulNotification extends SpatieBackupWasSuccessfulNotification
{
    protected $payload = [];

    public function toFakkel()
    {
        $this->payload = [
            'application' => $this->applicationName(),
            'disk_name' => $this->diskName(),
        ];

        $this->backupDestinationProperties()->each(function ($value, $name) {
            $this->payload[$name] = $value;
        });

        return (new Message())
            ->setMessage(trans('backup::notifications.backup_successful_subject', ['application_name' => $this->applicationName()]))
            ->setTags(['backup', 'successfull'])
            ->setPayload($this->payload);
    }
}
