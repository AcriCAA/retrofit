<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notification Preferences') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('notifications.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        {{-- Email Enabled --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <label for="email_enabled" class="text-sm font-medium text-gray-700">Email Notifications</label>
                                <p class="text-xs text-gray-500">Receive email alerts for new search results</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="email_enabled" value="0">
                                <input type="checkbox" name="email_enabled" value="1" class="sr-only peer" {{ $preferences->email_enabled ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        {{-- Email Frequency --}}
                        <div>
                            <label for="email_frequency" class="block text-sm font-medium text-gray-700">Email Frequency</label>
                            <select name="email_frequency" id="email_frequency" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="instant" {{ $preferences->email_frequency === 'instant' ? 'selected' : '' }}>Instant</option>
                                <option value="daily" {{ $preferences->email_frequency === 'daily' ? 'selected' : '' }}>Daily digest</option>
                                <option value="weekly" {{ $preferences->email_frequency === 'weekly' ? 'selected' : '' }}>Weekly digest</option>
                            </select>
                        </div>

                        {{-- Notify New Results --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <label for="notify_new_results" class="text-sm font-medium text-gray-700">New Results</label>
                                <p class="text-xs text-gray-500">Get notified when new matching items are found</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="notify_new_results" value="0">
                                <input type="checkbox" name="notify_new_results" value="1" class="sr-only peer" {{ $preferences->notify_new_results ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        {{-- Notify Price Drops --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <label for="notify_price_drops" class="text-sm font-medium text-gray-700">Price Drops</label>
                                <p class="text-xs text-gray-500">Get notified when saved items drop in price</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="notify_price_drops" value="0">
                                <input type="checkbox" name="notify_price_drops" value="1" class="sr-only peer" {{ $preferences->notify_price_drops ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <div class="pt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                Save Preferences
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
