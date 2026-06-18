<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Team;
use App\Http\Requests\StoreInvitationRequest;
use App\Services\InvitationService;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    protected InvitationService $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    public function store(StoreInvitationRequest $request, Team $team)
    {
        $this->invitationService->invite($team, Auth::user(), $request->validated()['search_key']);

        return back()->with('message', 'Zaproszenie zostało pomyślnie wysłane.');
    }

    public function accept(Invitation $invitation)
    {
        $this->invitationService->acceptInvitation($invitation, Auth::user());

        return redirect()->route('teams.show', $invitation->team_id)->with('message', 'Dołączyłeś do drużyny!');
    }

    public function decline(Invitation $invitation)
    {
        $this->invitationService->declineInvitation($invitation, Auth::user());

        return back()->with('message', 'Zaproszenie zostało odrzucone.');
    }
}