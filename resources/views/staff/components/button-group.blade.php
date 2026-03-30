@props([
    'buttons' => [], // Array of button configs
    'vertical' => false, // true for vertical layout
    'size' => 'md', // 'sm', 'md', 'lg'
    'class' => '',
])

@php
    $groupClass = $vertical ? 'btn-group-vertical' : 'btn-group';
    $sizeClass = $size === 'sm' ? 'btn-group-sm' : ($size === 'lg' ? 'btn-group-lg' : '');
    $wrapperClass = trim("{$groupClass} {$sizeClass} {$class}");
@endphp

<div class="{{ $wrapperClass }}" role="group">
    @foreach($buttons as $button)
        @include('staff.components.button', array_merge([
            'size' => $size,
        ], $button))
    @endforeach
</div>


