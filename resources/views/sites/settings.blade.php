@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Site Settings</h1>

        <form action="{{ route('sites.update', $site) }}" method="POST" class="bg-white rounded-lg shadow p-6">
            @csrf
            @method('PUT')

            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name', $site->name) }}" 
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="store_url" class="block text-sm font-medium text-gray-700 mb-2">Store URL</label>
                <input type="text" 
                       name="store_url" 
                       id="store_url" 
                       value="{{ old('store_url', $site->storeConfig?->store_url ?? config('store.default_url')) }}" 
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <p class="mt-1 text-sm text-gray-500">The Git repository URL for your plugin/theme store.</p>
                @error('store_url')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection 