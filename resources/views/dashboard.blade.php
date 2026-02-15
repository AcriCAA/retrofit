<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500">Active Searches</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $activeSearches }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-500">New Results</div>
                    <div class="mt-1 text-3xl font-semibold text-indigo-600">{{ $newResults }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <a href="{{ route('searches.create') }}" class="block">
                        <div class="text-sm font-medium text-gray-500">Start New Search</div>
                        <div class="mt-1 text-lg font-semibold text-indigo-600 hover:text-indigo-800">
                            Upload a photo &rarr;
                        </div>
                    </a>
                </div>
            </div>

            {{-- Recent Results --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Matches</h3>

                    @if($recentResults->isEmpty())
                        <p class="text-gray-500">No results yet. <a href="{{ route('searches.create') }}" class="text-indigo-600 hover:text-indigo-800">Start a new search</a> to find items.</p>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            @foreach($recentResults as $result)
                                @include('components.result-card', ['result' => $result])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
