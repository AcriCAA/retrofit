@php
    $marketplaceColors = [
        'ebay' => 'bg-blue-100 text-blue-800',
        'poshmark' => 'bg-pink-100 text-pink-800',
        'mercari' => 'bg-red-100 text-red-800',
        'thredup' => 'bg-green-100 text-green-800',
        'grailed' => 'bg-purple-100 text-purple-800',
    ];
    $colorClass = $marketplaceColors[$result->marketplace] ?? 'bg-gray-100 text-gray-800';
@endphp

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="resultActions({{ $result->id }}, '{{ $result->user_status }}')">
    {{-- Image --}}
    @if($result->image_url)
        <div class="h-48 bg-gray-100 overflow-hidden">
            <img src="{{ $result->image_url }}" alt="{{ $result->title }}" class="w-full h-full object-cover" loading="lazy">
        </div>
    @endif

    <div class="p-4">
        {{-- Title --}}
        <h4 class="font-semibold text-sm text-gray-900 line-clamp-2 mb-1">{{ $result->title }}</h4>

        {{-- Price & Condition --}}
        <div class="flex items-center gap-2 mb-2">
            @if($result->price)
                <span class="text-lg font-bold text-gray-900">${{ number_format($result->price, 2) }}</span>
            @endif
            @if($result->condition)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                    {{ $result->condition }}
                </span>
            @endif
        </div>

        {{-- Marketplace badge --}}
        <div class="flex items-center justify-between mb-3">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
                {{ ucfirst($result->marketplace) }}
            </span>
            @if($result->seller_name)
                <span class="text-xs text-gray-400">{{ $result->seller_name }}</span>
            @endif
        </div>

        {{-- Actions --}}
        <div class="flex items-center gap-2">
            <a href="{{ $result->url }}" target="_blank" rel="noopener noreferrer"
                class="flex-1 text-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-semibold rounded hover:bg-indigo-700 transition">
                View Listing
            </a>
            <button
                @click="updateStatus(status === 'saved' ? 'viewed' : 'saved')"
                :disabled="isUpdating"
                :class="status === 'saved' ? 'bg-yellow-100 text-yellow-700 border-yellow-300' : 'bg-white text-gray-500 border-gray-300'"
                class="px-2 py-1.5 border rounded text-xs font-medium hover:bg-gray-50 transition disabled:opacity-50"
                :title="status === 'saved' ? 'Unsave' : 'Save'"
            >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" x-show="status === 'saved'">
                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                </svg>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 20 20" x-show="status !== 'saved'">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                </svg>
            </button>
            <button
                @click="updateStatus('dismissed')"
                :disabled="isUpdating"
                x-show="status !== 'dismissed'"
                class="px-2 py-1.5 bg-white border border-gray-300 rounded text-xs font-medium text-gray-500 hover:bg-gray-50 transition disabled:opacity-50"
                title="Dismiss"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</div>
