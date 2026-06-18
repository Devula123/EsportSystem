<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');
        return $this->user()->id === $team->leader_id;
    }

    public function rules(): array
    {
        $team = $this->route('team');
        return [
            'name' => 'required|string|max:255|unique:teams,name,' . $team->id,
        ];
    }
}
