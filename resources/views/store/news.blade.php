@extends('store.layout')

@section('store-content')
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-2xl font-bold mb-4">Store News & Updates</h2>
    <div class="prose max-w-none">
        {!! $news !!}
    </div>
</div>
@endsection 