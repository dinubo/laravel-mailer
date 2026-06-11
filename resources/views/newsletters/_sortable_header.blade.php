@php
    $active = ($sort === $column);
    $nextDir = ($active && $dir === 'asc') ? 'desc' : 'asc';
@endphp
<a href="{{ request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDir, 'page' => 1]) }}"
   class="js-range-link text-reset text-decoration-none">
    {{ $label }}@if ($active) <span aria-hidden="true">{{ $dir === 'asc' ? '▲' : '▼' }}</span>@endif
</a>
