<div class="p-4 {{ $viewMode === 'grid' ? 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4' : 'flex flex-col gap-2' }}">
	@foreach($media as $item)
		<livewire:media::media-item
			:media="$item"
			:selected="in_array($item->id, $selectedMedia)"
			:bulkSelectMode="$bulkSelectMode"
			:key="'media-'.$item->id"
			:wire:key="'media-'.$item->id"
		/>
	@endforeach
</div>
