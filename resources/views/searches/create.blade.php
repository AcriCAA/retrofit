<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Search') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="itemChat()">
                <div class="p-6">
                    {{-- Image Upload --}}
                    <div x-show="!conversationId" class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload a photo of the item</label>

                        {{-- Category select --}}
                        @if($categories->count() > 1)
                            <div class="mb-4">
                                <select id="category_id" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" id="category_id" value="{{ $categories->first()?->id }}">
                        @endif

                        {{-- Dropzone --}}
                        <div
                            x-show="!imagePreview"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop($event)"
                            :class="isDragging ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300'"
                            class="border-2 border-dashed rounded-lg p-12 text-center cursor-pointer hover:border-indigo-400 transition"
                            @click="$refs.fileInput.click()"
                        >
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-600">
                                <span class="font-semibold text-indigo-600">Click to upload</span> or drag and drop
                            </p>
                            <p class="mt-1 text-xs text-gray-500">PNG, JPG, WEBP up to 10MB</p>
                            <input x-ref="fileInput" type="file" class="hidden" accept="image/*" @change="handleFileSelect($event)">
                        </div>

                        {{-- Image Preview --}}
                        <div x-show="imagePreview" class="relative">
                            <img :src="imagePreview" class="w-full max-h-64 object-contain rounded-lg border border-gray-200">
                            <button @click="removeImage()" class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Chat Messages --}}
                    <div x-ref="messagesContainer" class="space-y-4 max-h-[500px] overflow-y-auto mb-4" :class="messages.length ? 'min-h-[200px]' : ''">
                        <template x-for="msg in messages" :key="msg.id">
                            <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                                <div :class="msg.role === 'user'
                                    ? 'bg-indigo-600 text-white rounded-lg rounded-br-none px-4 py-2 max-w-[80%]'
                                    : 'bg-gray-100 text-gray-900 rounded-lg rounded-bl-none px-4 py-2 max-w-[80%]'">
                                    <div x-html="formatMessage(msg.content)" class="text-sm prose prose-sm max-w-none" :class="msg.role === 'user' ? 'prose-invert' : ''"></div>
                                    <div class="text-xs mt-1 opacity-60" x-text="formatTime(msg.created_at)"></div>
                                </div>
                            </div>
                        </template>

                        {{-- Loading indicator --}}
                        <div x-show="isSending" class="flex justify-start">
                            <div class="bg-gray-100 rounded-lg rounded-bl-none px-4 py-3">
                                <div class="flex space-x-1">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Error --}}
                    <div x-show="error" x-cloak class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm" x-text="error"></div>

                    {{-- Session expired --}}
                    <div x-show="sessionExpired" x-cloak class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg text-sm">
                        Your session has expired. <a href="{{ route('searches.create') }}" class="font-semibold underline">Refresh the page</a> to continue.
                    </div>

                    {{-- Search Created --}}
                    <div x-show="searchCreated" x-cloak class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                        <p class="font-semibold">Search created successfully!</p>
                        <p class="text-sm mt-1">We'll start looking across marketplaces and notify you when we find matches.</p>
                        <a x-show="searchCreated" :href="'/searches/' + searchCreated?.id" class="inline-flex items-center mt-2 text-sm font-semibold text-green-700 hover:text-green-800">
                            View Search &rarr;
                        </a>
                    </div>

                    {{-- Chat Input --}}
                    <div x-show="!searchCreated && !sessionExpired" class="flex gap-2">
                        <textarea
                            x-ref="chatInput"
                            x-model="message"
                            @keydown="handleKeydown($event)"
                            :disabled="isSending"
                            :placeholder="conversationId ? 'Type your response...' : 'Describe what you\'re looking for (optional)...'"
                            rows="2"
                            class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm resize-none disabled:opacity-50"
                        ></textarea>
                        <button
                            @click="sendMessage()"
                            :disabled="isSending || (!conversationId && !imageFile)"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed self-end"
                        >
                            <span x-show="!isSending">Send</span>
                            <span x-show="isSending">...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
