<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        {{-- Dismissal Feedback Modal (shared across all pages that render result-cards) --}}
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

                        {{-- Similar results bulk dismiss prompt --}}
                        <template x-if="isRefined && similarResults.length > 0 && !bulkDismissed">
                            <div class="mx-4 mb-3 px-3.5 py-2.5 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-900">
                                <p class="font-medium mb-2">Found <span x-text="similarResults.length"></span> other result<template x-if="similarResults.length !== 1"><span>s</span></template> with the same issue:</p>
                                <ul class="space-y-1 mb-3">
                                    <template x-for="r in similarResults" :key="r.id">
                                        <li class="text-xs text-amber-800" x-text="r.title + (r.price ? ' — $' + parseFloat(r.price).toFixed(0) : '') + ' (' + r.marketplace + ')'"></li>
                                    </template>
                                </ul>
                                <div class="flex gap-2">
                                    <button
                                        @click="bulkDismiss()"
                                        :disabled="isBulkDismissing"
                                        class="px-3 py-1.5 bg-amber-700 text-white text-xs font-medium rounded-lg hover:bg-amber-800 transition disabled:opacity-50"
                                    >
                                        <span x-show="!isBulkDismissing">Dismiss these too</span>
                                        <span x-show="isBulkDismissing">Dismissing...</span>
                                    </button>
                                    <button
                                        @click="bulkDismissed = true"
                                        class="px-3 py-1.5 bg-white border border-amber-300 text-amber-700 text-xs font-medium rounded-lg hover:bg-amber-50 transition"
                                    >
                                        Skip
                                    </button>
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
    </body>
</html>
