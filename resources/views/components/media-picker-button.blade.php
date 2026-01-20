<div class="media-picker-button-wrapper" {{ $attributes }}>
	<x-artisanpack-button
		type="button"
		:variant="$variant"
		:size="$size"
		x-data
		@click="$dispatch('open-media-picker', { context: {{ Js::from($context) }} })"
	>
		@if ($icon)
			<x-artisanpack-icon :name="$icon" class="w-4 h-4 mr-2"/>
		@endif
		{{ $label }}
	</x-artisanpack-button>

	@if ($withPicker)
		<livewire:media::media-picker
			:multi-select="$multiSelect"
			:max-selections="$maxSelections"
			:accept-types="$acceptTypes"
			:load-count="$loadCount"
			:context="$context"
		/>
	@endif
</div>
