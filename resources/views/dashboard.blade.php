@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
                <h2 class="text-2xl font-semibold mb-6">Dashboard</h2>

                <!-- Store URL Configuration -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium mb-4">Store Configuration</h3>
                    <form action="{{ route('admin.store.settings') }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        
                        <div>
                            <label for="store_url" class="block text-sm font-medium text-gray-700">Store URL</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="text" 
                                       name="store_url" 
                                       id="store_url" 
                                       value="{{ old('store_url', $storeUrl ?? config('store.default_url')) }}" 
                                       class="flex-1 min-w-0 block w-full px-3 py-2 rounded-md border border-gray-300 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>
                            @error('store_url')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-600 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-300 disabled:opacity-25 transition">
                                Save Store Settings
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Other Dashboard Content -->
                <div class="mt-8">
                    <!-- Add your other dashboard content here -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 