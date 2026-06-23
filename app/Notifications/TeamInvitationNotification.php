<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification
{
    use Queueable;

    protected array $details;

    public function __construct(array $details)
    {
        $this->details = $details;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'team_id' => $this->details['team_id'],
            'team_name' => $this->details['team_name'],
            'invitation_id' => $this->details['invitation_id'],
            'message' => "Zaproszono Cię do drużyny " . $this->details['team_name'] . ".",
        ];
    }
}
