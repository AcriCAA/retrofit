<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Invitations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash: newly generated invite URL --}}
            @if (session('invite_url'))
                <div
                    x-data="{ copied: false }"
                    class="bg-green-50 border border-green-200 rounded-lg p-4"
                >
                    <p class="text-sm font-medium text-green-800 mb-2">Invite link generated — share this URL:</p>
                    <div class="flex items-center gap-2">
                        <input
                            id="invite-url"
                            type="text"
                            readonly
                            value="{{ session('invite_url') }}"
                            class="flex-1 text-sm bg-white border border-green-300 rounded-md px-3 py-2 text-gray-700 focus:outline-none"
                        />
                        <button
                            @click="navigator.clipboard.writeText('{{ session('invite_url') }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md border border-green-400 text-green-700 bg-white hover:bg-green-50 transition"
                        >
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied">Copied!</span>
                        </button>
                    </div>
                </div>
            @endif

            @if (session('status'))
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Generate button --}}
            <div class="bg-white shadow rounded-lg p-6 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Generate a single-use invite link valid for 7 days.</p>
                </div>
                <form method="POST" action="{{ route('invitations.store') }}">
                    @csrf
                    <x-primary-button type="submit">
                        {{ __('Generate Invite Link') }}
                    </x-primary-button>
                </form>
            </div>

            {{-- Invitations table --}}
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Token</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($invitations as $invitation)
                            @php
                                if ($invitation->accepted_at) {
                                    $status = 'Accepted';
                                    $statusClass = 'bg-green-100 text-green-800';
                                } elseif ($invitation->expires_at->isPast()) {
                                    $status = 'Expired';
                                    $statusClass = 'bg-red-100 text-red-800';
                                } else {
                                    $status = 'Pending';
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                }
                            @endphp
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-700">
                                    {{ substr($invitation->token, 0, 8) }}…
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $invitation->created_at->toFormattedDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $invitation->expires_at->toFormattedDateString() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    @if ($status === 'Pending')
                                        <form method="POST" action="{{ route('invitations.destroy', $invitation) }}" onsubmit="return confirm('Revoke this invitation?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-danger-button type="submit" class="text-xs py-1 px-2">
                                                Revoke
                                            </x-danger-button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">
                                    No invitations yet. Generate one above to invite someone.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($invitations->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $invitations->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
