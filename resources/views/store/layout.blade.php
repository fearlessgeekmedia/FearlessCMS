@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <nav class="flex space-x-4">
            <a href="{{ route('store.index') }}" class="px-4 py-2 rounded {{ request()->routeIs('store.index') ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                Home
            </a>
            <a href="{{ route('store.featured') }}" class="px-4 py-2 rounded {{ request()->routeIs('store.featured') ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                Featured
            </a>
            <a href="{{ route('store.news') }}" class="px-4 py-2 rounded {{ request()->routeIs('store.news') ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                News
            </a>
            <a href="{{ route('store.browse') }}" class="px-4 py-2 rounded {{ request()->routeIs('store.browse') ? 'bg-blue-500 text-white' : 'bg-gray-200' }}">
                Browse
            </a>
        </nav>
    </div>

    @yield('store-content')
</div>
@endsection 