<div class="media-grid {{ $viewMode === 'grid' ? 'grid-view' : 'list-view' }}">
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

<style>
	.media-grid.grid-view {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
		gap: 1rem;
		padding: 1rem;
	}

	.media-grid.list-view {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
		padding: 1rem;
	}

	@media (max-width: 768px) {
		.media-grid.grid-view {
			grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
		}
	}
</style>
