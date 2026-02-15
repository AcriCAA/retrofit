<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $searchRequest->title }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('searches.edit', $searchRequest) }}" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    Edit
                </a>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $searchRequest->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $searchRequest->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : '' }}
                    {{ $searchRequest->status === 'completed' ? 'bg-gray-100 text-gray-800' : '' }}
                ">
                    {{ ucfirst($searchRequest->status) }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {{-- Sidebar: Search Details --}}
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 space-y-4">
                        @if($searchRequest->image_path)
                            <img src="{{ Storage::url($searchRequest->image_path) }}" alt="{{ $searchRequest->title }}" class="w-full rounded-lg">
                        @endif

                        @if($searchRequest->description)
                            <p class="text-sm text-gray-600">{{ $searchRequest->description }}</p>
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
                            <div class="border-t pt-2">
                                <div class="text-sm">
                                    <span class="font-medium text-gray-500">Price Range:</span>
                                    <span class="text-gray-900">
                                        @if($searchRequest->min_price && $searchRequest->max_price)
                                            ${{ $searchRequest->min_price }} - ${{ $searchRequest->max_price }}
                                        @elseif($searchRequest->min_price)
                                            From ${{ $searchRequest->min_price }}
                                        @else
                                            Up to ${{ $searchRequest->max_price }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endif

                        <div class="border-t pt-2 text-xs text-gray-400">
                            Searching every {{ $searchRequest->search_frequency_minutes }} min
                            @if($searchRequest->last_searched_at)
                                <br>Last searched {{ $searchRequest->last_searched_at->diffForHumans() }}
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Results Grid --}}
                <div class="lg:col-span-3">
                    @if($searchRequest->results->isEmpty())
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                            <p class="text-gray-500">No results found yet. We're searching across marketplaces — you'll be notified when matches appear.</p>
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
