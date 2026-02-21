<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $searchRequest->title }}
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('searches.edit', $searchRequest) }}" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Edit
                </a>
                <form method="POST" action="{{ route('searches.destroy', $searchRequest) }}"
                    onsubmit="return confirm('Delete this search and all its results? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-white border border-red-300 rounded-md text-sm font-medium text-red-600 hover:bg-red-50 transition">
                        Delete
                    </button>
                </form>
                @if($searchRequest->status !== 'completed')
                    <form method="POST" action="{{ route('searches.update', $searchRequest) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="status" value="{{ $searchRequest->status === 'active' ? 'paused' : 'active' }}">
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm font-medium transition
                            {{ $searchRequest->status === 'active'
                                ? 'bg-yellow-50 border border-yellow-300 text-yellow-700 hover:bg-yellow-100'
                                : 'bg-green-50 border border-green-300 text-green-700 hover:bg-green-100' }}">
                            @if($searchRequest->status === 'active')
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Pause Search
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Resume Search
                            @endif
                        </button>
                    </form>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        Completed
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {{-- Sidebar --}}
                <div class="lg:col-span-1 space-y-4">

                    {{-- Search Activity --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Search Activity</h3>

                        @if($searchRequest->status === 'active')
                            <div class="flex items-center gap-2 mb-3">
                                <span class="relative flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
                                </span>
                                <span class="text-sm text-green-700 font-medium">Actively searching</span>
                            </div>
                        @elseif($searchRequest->status === 'paused')
                            <div class="flex items-center gap-2 mb-3">
                                <span class="inline-flex rounded-full h-2.5 w-2.5 bg-yellow-400"></span>
                                <span class="text-sm text-yellow-700 font-medium">Paused</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2 mb-3">
                                <span class="inline-flex rounded-full h-2.5 w-2.5 bg-gray-400"></span>
                                <span class="text-sm text-gray-600 font-medium">Completed</span>
                            </div>
                        @endif

                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Frequency</span>
                                <span class="text-gray-900 font-medium">
                                    @if($searchRequest->search_frequency_minutes < 60)
                                        Every {{ $searchRequest->search_frequency_minutes }} min
                                    @elseif($searchRequest->search_frequency_minutes === 60)
                                        Every hour
                                    @elseif($searchRequest->search_frequency_minutes < 1440)
                                        Every {{ $searchRequest->search_frequency_minutes / 60 }} hours
                                    @else
                                        Daily
                                    @endif
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-500">Last searched</span>
                                <span class="text-gray-900 font-medium">
                                    @if($searchRequest->last_searched_at)
                                        {{ $searchRequest->last_searched_at->diffForHumans() }}
                                    @else
                                        Never
                                    @endif
                                </span>
                            </div>

                            @if($searchRequest->status === 'active')
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Next search</span>
                                    <span class="text-gray-900 font-medium">
                                        @if($searchRequest->last_searched_at)
                                            {{ $searchRequest->last_searched_at->addMinutes($searchRequest->search_frequency_minutes)->diffForHumans() }}
                                        @else
                                            Queued
                                        @endif
                                    </span>
                                </div>
                            @endif

                            <div class="flex justify-between">
                                <span class="text-gray-500">Created</span>
                                <span class="text-gray-900 font-medium">{{ $searchRequest->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Results Summary --}}
                    @if($resultStats['total'] > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-3">Results Summary</h3>

                            <div class="grid grid-cols-3 gap-2 mb-3">
                                <div class="text-center p-2 bg-blue-50 rounded-lg">
                                    <div class="text-lg font-bold text-blue-700">{{ $resultStats['total'] }}</div>
                                    <div class="text-xs text-blue-600">Total</div>
                                </div>
                                <div class="text-center p-2 bg-green-50 rounded-lg">
                                    <div class="text-lg font-bold text-green-700">{{ $resultStats['new'] }}</div>
                                    <div class="text-xs text-green-600">New</div>
                                </div>
                                <div class="text-center p-2 bg-yellow-50 rounded-lg">
                                    <div class="text-lg font-bold text-yellow-700">{{ $resultStats['saved'] }}</div>
                                    <div class="text-xs text-yellow-600">Saved</div>
                                </div>
                            </div>

                            {{-- Marketplace breakdown --}}
                            <div class="space-y-1.5">
                                @php
                                    $marketplaceColors = [
                                        'ebay' => 'bg-blue-500',
                                        'poshmark' => 'bg-pink-500',
                                        'mercari' => 'bg-red-500',
                                        'thredup' => 'bg-green-500',
                                        'grailed' => 'bg-purple-500',
                                    ];
                                @endphp
                                @foreach($resultStats['marketplaces'] as $marketplace => $count)
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $marketplaceColors[$marketplace] ?? 'bg-gray-400' }}"></span>
                                            <span class="text-gray-600 capitalize">{{ $marketplace }}</span>
                                        </div>
                                        <span class="text-gray-900 font-medium">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Search Criteria --}}
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Search Criteria</h3>

                        @if($searchRequest->image_path)
                            <img src="{{ Storage::url($searchRequest->image_path) }}" alt="{{ $searchRequest->title }}" class="w-full rounded-lg mb-3">
                        @endif

                        @if($searchRequest->description)
                            <p class="text-sm text-gray-600 mb-3">{{ $searchRequest->description }}</p>
                        @endif

                        <div class="space-y-2">
                            @foreach($searchRequest->attributes as $attr)
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-gray-500 capitalize">{{ str_replace('_', ' ', $attr->key) }}</span>
                                    <span class="text-gray-900">{{ $attr->value }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if($searchRequest->min_price || $searchRequest->max_price)
                            <div class="border-t mt-3 pt-3">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium text-gray-500">Price range</span>
                                    <span class="text-gray-900">
                                        @if($searchRequest->min_price && $searchRequest->max_price)
                                            ${{ number_format($searchRequest->min_price, 0) }} – ${{ number_format($searchRequest->max_price, 0) }}
                                        @elseif($searchRequest->min_price)
                                            From ${{ number_format($searchRequest->min_price, 0) }}
                                        @else
                                            Up to ${{ number_format($searchRequest->max_price, 0) }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Results Grid --}}
                <div class="lg:col-span-3">
                    @if($searchRequest->results->isEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                            <div class="mx-auto w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            @if($searchRequest->last_searched_at)
                                <p class="text-gray-900 font-medium mb-1">No matches yet</p>
                                <p class="text-sm text-gray-500">
                                    We last checked {{ $searchRequest->last_searched_at->diffForHumans() }} and will search again
                                    {{ $searchRequest->last_searched_at->addMinutes($searchRequest->search_frequency_minutes)->diffForHumans() }}.
                                </p>
                            @elseif($searchRequest->status === 'active')
                                <p class="text-gray-900 font-medium mb-1">Search queued</p>
                                <p class="text-sm text-gray-500">Your search is in the queue and will run shortly. We'll notify you when matches appear.</p>
                            @elseif($searchRequest->status === 'paused')
                                <p class="text-gray-900 font-medium mb-1">Search is paused</p>
                                <p class="text-sm text-gray-500">Resume this search to start finding results across marketplaces.</p>
                            @else
                                <p class="text-gray-500">No results were found for this search.</p>
                            @endif
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach($searchRequest->results as $result)
                                @include('components.result-card', ['result' => $result])
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</x-app-layout>
