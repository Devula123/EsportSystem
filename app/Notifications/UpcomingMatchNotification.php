<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UpcomingMatchNotification extends Notification
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
            'match_id' => $this->details['match_id'],
            'tournament_id' => $this->details['tournament_id'],
            'tournament_name' => $this->details['tournament_name'],
            'opponent_name' => $this->details['opponent_name'],
            'round_number' => $this->details['round_number'],
            'message' => "Twój zespół gra w rundzie " . $this->details['round_number'] . " turnieju " . $this->details['tournament_name'] . " przeciwko " . $this->details['opponent_name'] . ".",
        ];
    }
}
