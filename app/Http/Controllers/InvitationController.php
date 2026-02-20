<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function index(): View
    {
        $invitations = Invitation::where('invited_by', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('invitations.index', compact('invitations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $invitation = Invitation::create([
            'token' => Str::random(32),
            'invited_by' => auth()->id(),
            'expires_at' => now()->addDays(7),
        ]);

        return redirect()->route('invitations.index')
            ->with('invite_url', $invitation->shareableUrl());
    }

    public function destroy(Invitation $invitation): RedirectResponse
    {
        abort_unless($invitation->invited_by === auth()->id(), 403);

        $invitation->delete();

        return redirect()->route('invitations.index')
            ->with('status', 'Invitation revoked.');
    }
}
