<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTournamentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'game_name' => 'required|string|max:255',
            'max_teams' => 'required|integer|min:2',
            'start_date' => 'required|date',
        ];
    }
}
