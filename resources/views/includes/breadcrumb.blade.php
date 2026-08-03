{{--
    Breadcrumb component.

    Usage:
    @include('includes.breadcrumb', ['items' => [
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Events'],
    ]])
--}}
<nav aria-label="Breadcrumb" class="mb-1">
    <ol class="breadcrumb">
        <li>
            <a href="{{ route('admin.dashboard') }}" class="breadcrumb-link" aria-label="Home">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
                </svg>
            </a>
        </li>

        @foreach ($items ?? [] as $item)
            <li class="flex items-center gap-1.5">
                <svg class="breadcrumb-separator" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
                @if (!empty($item['url']))
                    <a href="{{ $item['url'] }}" class="breadcrumb-link">{{ $item['label'] }}</a>
                @else
                    <span class="breadcrumb-current" aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
