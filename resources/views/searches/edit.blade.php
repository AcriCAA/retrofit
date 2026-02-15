<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit: {{ $searchRequest->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('searches.update', $searchRequest) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        {{-- Status --}}
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="active" {{ $searchRequest->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="paused" {{ $searchRequest->status === 'paused' ? 'selected' : '' }}>Paused</option>
                                <option value="completed" {{ $searchRequest->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            </select>
                        </div>

                        {{-- Search Frequency --}}
                        <div>
                            <label for="search_frequency_minutes" class="block text-sm font-medium text-gray-700">Search Frequency</label>
                            <select name="search_frequency_minutes" id="search_frequency_minutes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="15" {{ $searchRequest->search_frequency_minutes == 15 ? 'selected' : '' }}>Every 15 minutes</option>
                                <option value="30" {{ $searchRequest->search_frequency_minutes == 30 ? 'selected' : '' }}>Every 30 minutes</option>
                                <option value="60" {{ $searchRequest->search_frequency_minutes == 60 ? 'selected' : '' }}>Every hour</option>
                                <option value="360" {{ $searchRequest->search_frequency_minutes == 360 ? 'selected' : '' }}>Every 6 hours</option>
                                <option value="1440" {{ $searchRequest->search_frequency_minutes == 1440 ? 'selected' : '' }}>Daily</option>
                                <option value="10080" {{ $searchRequest->search_frequency_minutes == 10080 ? 'selected' : '' }}>Weekly</option>
                            </select>
                        </div>

                        {{-- Price Range --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="min_price" class="block text-sm font-medium text-gray-700">Min Price ($)</label>
                                <input type="number" name="min_price" id="min_price" step="0.01" min="0"
                                    value="{{ old('min_price', $searchRequest->min_price) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    placeholder="0.00">
                            </div>
                            <div>
                                <label for="max_price" class="block text-sm font-medium text-gray-700">Max Price ($)</label>
                                <input type="number" name="max_price" id="max_price" step="0.01" min="0"
                                    value="{{ old('max_price', $searchRequest->max_price) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    placeholder="0.00">
                            </div>
                        </div>

                        {{-- Attributes (read-only display) --}}
                        @if($searchRequest->attributes->isNotEmpty())
                            <div class="border-t pt-4">
                                <h3 class="text-sm font-medium text-gray-700 mb-2">Search Attributes</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($searchRequest->attributes as $attr)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ ucfirst($attr->key) }}: {{ $attr->value }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                                <ul class="list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="flex justify-between">
                            <a href="{{ route('searches.show', $searchRequest) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                                Update Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
