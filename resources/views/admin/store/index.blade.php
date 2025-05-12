@extends('admin.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-semibold">Store Management</h2>
                    <a href="{{ route('admin.store.sync') }}" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Sync Store
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Store Statistics -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Store Statistics</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Plugins:</span>
                                <span class="font-semibold">{{ $stats['plugins'] ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Themes:</span>
                                <span class="font-semibold">{{ $stats['themes'] ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Last Sync:</span>
                                <span class="font-semibold">{{ $lastSync ? $lastSync->diffForHumans() : 'Never' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Store Settings -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Store Settings</h3>
                        <form action="{{ route('admin.store.settings') }}" method="POST">
                            @csrf
                            @method('PUT')
                            
                            <div class="mb-4">
                                <label for="store_url" class="block text-sm font-medium text-gray-700 mb-2">Store URL</label>
                                <input type="text" 
                                       name="store_url" 
                                       id="store_url" 
                                       value="{{ old('store_url', $storeUrl) }}" 
                                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @error('store_url')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                    Save Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="mt-8">
                    <h3 class="text-lg font-semibold mb-4">Recent Activity</h3>
                    <div class="bg-gray-50 rounded-lg p-6">
                        @if(isset($activity) && count($activity) > 0)
                            <div class="space-y-4">
                                @foreach($activity as $item)
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="font-medium">{{ $item['action'] }}</span>
                                            <span class="text-gray-600">{{ $item['package'] }}</span>
                                        </div>
                                        <span class="text-sm text-gray-500">{{ $item['date']->diffForHumans() }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500">No recent activity</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 