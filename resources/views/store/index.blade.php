@extends('store.layout')

@section('store-content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-8">
    <!-- Featured Section -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Featured</h2>
        <div class="prose max-w-none">
            {!! $featured !!}
        </div>
    </div>

    <!-- News Section -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Latest News</h2>
        <div class="prose max-w-none">
            {!! $news !!}
        </div>
    </div>
</div>

<!-- Quick Browse Section -->
<div class="mt-8 bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold mb-4">Browse Packages</h2>
    <div class="flex space-x-4">
        <a href="{{ route('store.browse', ['type' => 'plugin']) }}" class="px-6 py-3 bg-blue-500 text-white rounded hover:bg-blue-600">
            Browse Plugins
        </a>
        <a href="{{ route('store.browse', ['type' => 'theme']) }}" class="px-6 py-3 bg-green-500 text-white rounded hover:bg-green-600">
            Browse Themes
        </a>
    </div>
</div>
@endsection 