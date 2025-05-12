@extends('store.layout')

@section('store-content')
<div class="bg-white rounded-lg shadow p-6">
    <div class="mb-6">
        <form action="{{ route('store.browse') }}" method="GET" class="flex gap-4">
            <div class="flex-1">
                <input type="text" 
                       name="q" 
                       value="{{ $query }}" 
                       placeholder="Search packages..." 
                       class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <select name="type" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Types</option>
                <option value="plugin" {{ $type === 'plugin' ? 'selected' : '' }}>Plugins</option>
                <option value="theme" {{ $type === 'theme' ? 'selected' : '' }}>Themes</option>
            </select>
            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                Search
            </button>
        </form>
    </div>

    @if($packages->isEmpty())
        <div class="text-center py-8">
            <p class="text-gray-500">No packages found matching your search criteria.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($packages as $package)
                <div class="border rounded-lg p-4 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xl font-semibold">{{ $package['name'] }}</h3>
                        <span class="px-2 py-1 text-sm rounded {{ $package['type'] === 'plugin' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                            {{ ucfirst($package['type']) }}
                        </span>
                    </div>
                    <p class="text-gray-600 mb-4">{{ $package['description'] }}</p>
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span>Version {{ $package['version'] }}</span>
                        <span>By {{ $package['author'] }}</span>
                    </div>
                    <div class="mt-4">
                        <button 
                            onclick="installPackage('{{ $package['name'] }}', '{{ $package['type'] }}')"
                            class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Install
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
function installPackage(package, type) {
    if (!confirm(`Are you sure you want to install ${package}?`)) {
        return;
    }

    fetch(`{{ route('store.install', '') }}/${package}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ type })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            alert(data.message);
            // Optionally refresh the page or update the UI
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while installing the package.');
    });
}

function uninstallPackage(package, type) {
    if (!confirm(`Are you sure you want to uninstall ${package}?`)) {
        return;
    }

    fetch(`{{ route('store.uninstall', '') }}/${package}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ type })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            alert(data.message);
            // Optionally refresh the page or update the UI
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while uninstalling the package.');
    });
}
</script>
@endpush
@endsection 