<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InvitationAcceptedNotification extends Notification
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
            'user_id' => $this->details['user_id'],
            'user_name' => $this->details['user_name'],
            'team_name' => $this->details['team_name'],
            'message' => "Gracz " . $this->details['user_name'] . " zaakceptował zaproszenie do drużyny.",
        ];
    }
}
