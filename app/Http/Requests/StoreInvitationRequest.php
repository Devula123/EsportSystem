<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $team = $this->route('team');
        return $this->user()->id === $team->leader_id;
    }

    public function rules(): array
    {
        return [
            'search_key' => 'required|string',
        ];
    }
}
