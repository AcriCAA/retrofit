<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function edit(Request $request)
    {
        $preferences = $request->user()->notificationPreference
            ?? NotificationPreference::create(['user_id' => $request->user()->id]);

        return view('settings.notifications', compact('preferences'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'email_enabled' => 'boolean',
            'email_frequency' => 'in:instant,daily,weekly',
            'notify_new_results' => 'boolean',
            'notify_price_drops' => 'boolean',
        ]);

        $preferences = $request->user()->notificationPreference
            ?? new NotificationPreference(['user_id' => $request->user()->id]);

        $preferences->fill([
            'email_enabled' => $validated['email_enabled'] ?? false,
            'email_frequency' => $validated['email_frequency'] ?? 'instant',
            'notify_new_results' => $validated['notify_new_results'] ?? false,
            'notify_price_drops' => $validated['notify_price_drops'] ?? false,
        ]);
        $preferences->save();

        return redirect()->route('notifications.edit')
            ->with('success', 'Notification preferences updated.');
    }
}
