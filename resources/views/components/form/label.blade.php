@props(['for' => null, 'value' => null])

<label @if($for) for="{{ $for }}" @endif class="block text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] mb-1">
	{{ $value ?? $slot }}
</label>


