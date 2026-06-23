<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    protected InvitationService $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $invitation = $this->invitationService->sendInvitation(auth()->user(), $validated);

        return response()->json([
            'message' => 'Zaproszenie zostało wysłane pomyślnie.',
            'invitation' => $invitation,
        ], 201);
    }

    public function accept(Invitation $invitation): JsonResponse
    {
        $this->invitationService->acceptInvitation($invitation, auth()->user());

        return response()->json([
            'message' => 'Zaproszenie zostało zaakceptowane. Dołączyłeś do drużyny.',
        ]);
    }

    public function decline(Invitation $invitation): JsonResponse
    {
        $this->invitationService->declineInvitation($invitation, auth()->user());

        return response()->json([
            'message' => 'Zaproszenie zostało odrzucone.',
        ]);
    }
}
