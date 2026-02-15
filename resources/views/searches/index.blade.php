<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('My Searches') }}
            </h2>
            <a href="{{ route('searches.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                New Search
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if($searches->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-12 text-center">
                    <p class="text-gray-500 mb-4">You haven't created any searches yet.</p>
                    <a href="{{ route('searches.create') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                        Upload a photo to get started &rarr;
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($searches as $search)
                        <a href="{{ route('searches.show', $search) }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition">
                            @if($search->image_path)
                                <div class="h-48 bg-gray-100 overflow-hidden">
                                    <img src="{{ Storage::url($search->image_path) }}" alt="{{ $search->title }}" class="w-full h-full object-cover">
                                </div>
                            @endif
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <h3 class="font-semibold text-gray-900 truncate">{{ $search->title }}</h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $search->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $search->status === 'paused' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $search->status === 'completed' ? 'bg-gray-100 text-gray-800' : '' }}
                                    ">
                                        {{ ucfirst($search->status) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                    <span>{{ $search->results_count }} results</span>
                                    @if($search->new_results_count > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            {{ $search->new_results_count }} new
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $searches->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
