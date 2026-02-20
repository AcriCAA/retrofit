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

{{-- Dismissal Feedback Modal --}}
<div
    x-data="dismissalChat()"
    @open-dismissal-modal.window="open($event.detail)"
    x-show="isOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    style="display: none;"
    @keydown.escape.window="close()"
>
    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50" @click="close()"></div>

    {{-- Modal Panel --}}
    <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.stop
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">Help us improve your search</h3>
            <button @click="close()" class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Body: product tile + chat --}}
        <div class="flex flex-1 overflow-hidden">

            {{-- Product reference tile --}}
            <div class="w-48 flex-shrink-0 border-r border-gray-100 bg-gray-50 flex flex-col">
                <div class="h-40 bg-gray-100 overflow-hidden flex-shrink-0">
                    <img
                        :src="result.imageUrl"
                        :alt="result.title"
                        class="w-full h-full object-cover"
                        x-show="result.imageUrl"
                    >
                    <div x-show="!result.imageUrl" class="w-full h-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
                <div class="p-3 flex-1">
                    <p class="text-xs font-semibold text-gray-900 line-clamp-3 mb-2" x-text="result.title"></p>
                    <p class="text-sm font-bold text-gray-900" x-text="formatPrice(result.price)"></p>
                    <template x-if="result.condition">
                        <span class="inline-block mt-1 px-1.5 py-0.5 bg-gray-100 text-gray-600 text-xs rounded" x-text="result.condition"></span>
                    </template>
                    <template x-if="result.marketplace">
                        <p class="text-xs text-gray-400 mt-1" x-text="result.marketplace"></p>
                    </template>
                </div>
            </div>

            {{-- Chat area --}}
            <div class="flex-1 flex flex-col overflow-hidden">

                {{-- Messages --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-3" x-ref="messagesContainer">

                    {{-- Loading state --}}
                    <template x-if="isStarting">
                        <div class="flex items-center gap-2 text-gray-400 text-sm">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span>Starting conversation...</span>
                        </div>
                    </template>

                    <template x-for="msg in messages" :key="msg.id">
                        <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div
                                :class="msg.role === 'user'
                                    ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-sm'
                                    : 'bg-gray-100 text-gray-900 rounded-2xl rounded-tl-sm'"
                                class="max-w-xs px-3.5 py-2.5 text-sm"
                                x-html="formatMessage(msg.content)"
                            ></div>
                        </div>
                    </template>

                    {{-- Sending indicator --}}
                    <template x-if="isSending">
                        <div class="flex justify-start">
                            <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-3.5 py-2.5">
                                <span class="flex gap-1">
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                                    <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                                </span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Refined success banner --}}
                <template x-if="isRefined">
                    <div class="mx-4 mb-3 px-3.5 py-2.5 bg-green-50 border border-green-200 rounded-xl text-sm text-green-800 flex items-start gap-2">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <div>
                            <p class="font-medium">Search criteria updated</p>
                            <p class="text-green-700 text-xs mt-0.5" x-text="refinementSummary"></p>
                        </div>
                    </div>
                </template>

                {{-- Error --}}
                <template x-if="error">
                    <div class="mx-4 mb-3 px-3 py-2 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700" x-text="error"></div>
                </template>

                {{-- Input --}}
                <div class="border-t border-gray-100 p-3">
                    <template x-if="!isRefined">
                        <div class="flex gap-2">
                            <textarea
                                x-model="message"
                                @keydown="handleKeydown"
                                :disabled="isSending || isStarting"
                                rows="2"
                                placeholder="Type your response..."
                                class="flex-1 resize-none border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:opacity-50"
                            ></textarea>
                            <button
                                @click="sendMessage()"
                                :disabled="isSending || isStarting || !message.trim()"
                                class="self-end px-3 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </div>
                    </template>
                    <template x-if="isRefined">
                        <button
                            @click="close()"
                            class="w-full py-2 bg-gray-900 text-white text-sm font-medium rounded-xl hover:bg-gray-700 transition"
                        >
                            Done
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

</x-app-layout>
