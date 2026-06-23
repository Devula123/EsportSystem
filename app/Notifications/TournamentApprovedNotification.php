<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TournamentApprovedNotification extends Notification
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
            'tournament_id' => $this->details['tournament_id'],
            'tournament_name' => $this->details['tournament_name'],
            'message' => "Turniej " . $this->details['tournament_name'] . " został zatwierdzony i jest gotowy do zapisów!",
        ];
    }
}
