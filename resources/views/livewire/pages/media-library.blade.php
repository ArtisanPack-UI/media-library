<div class="media-library-container">
	{{-- Header --}}
	<div class="media-library-header">
		<div class="header-left">
			<h1 class="page-title">{{ __('Media Library') }}</h1>

			@if($this->currentFolder)
				<div class="breadcrumb">
					<a href="#" wire:click.prevent="setFolder(null)">{{ __('All Media') }}</a>
					<span class="separator">/</span>
					<span class="current">{{ $this->currentFolder->name }}</span>
				</div>
			@endif
		</div>

		<div class="header-actions">
			<button type="button" wire:click="toggleViewMode" class="btn btn-icon" title="{{ __('Toggle View') }}">
				@if($viewMode === 'grid')
					<x-icon-fas-list />
				@else
					<x-icon-fas-grid />
				@endif
			</button>

			<button type="button" wire:click="toggleBulkSelect" class="btn btn-secondary">
				@if($bulkSelectMode)
					{{ __('Cancel Selection') }}
				@else
					{{ __('Select Multiple') }}
				@endif
			</button>

			<a href="{{ route('admin.media.add') }}" class="btn btn-primary">
				<x-icon-fas-upload class="mr-2" />
				{{ __('Upload Media') }}
			</a>
		</div>
	</div>

	{{-- Filters Bar --}}
	<div class="media-filters">
		<div class="filter-group">
			<label for="search">{{ __('Search') }}</label>
			<input
				type="text"
				id="search"
				wire:model.live.debounce.300ms="search"
				placeholder="{{ __('Search media...') }}"
				class="form-input"
			/>
		</div>

		<div class="filter-group">
			<label for="type">{{ __('Type') }}</label>
			<select id="type" wire:model.live="type" class="form-select">
				<option value="">{{ __('All Types') }}</option>
				<option value="image">{{ __('Images') }}</option>
				<option value="video">{{ __('Videos') }}</option>
				<option value="audio">{{ __('Audio') }}</option>
				<option value="document">{{ __('Documents') }}</option>
			</select>
		</div>

		<div class="filter-group">
			<label for="folder">{{ __('Folder') }}</label>
			<select id="folder" wire:model.live="folderId" class="form-select">
				<option value="">{{ __('All Folders') }}</option>
				@foreach($this->folders as $folder)
					<option value="{{ $folder->id }}">{{ $folder->name }}</option>
					@if($folder->children->isNotEmpty())
						@foreach($folder->children as $child)
							<option value="{{ $child->id }}">-- {{ $child->name }}</option>
						@endforeach
					@endif
				@endforeach
			</select>
		</div>

		<div class="filter-group">
			<label for="sort">{{ __('Sort By') }}</label>
			<select id="sort" wire:model.live="sortBy" class="form-select">
				<option value="created_at">{{ __('Date Added') }}</option>
				<option value="title">{{ __('Title') }}</option>
				<option value="file_name">{{ __('File Name') }}</option>
				<option value="file_size">{{ __('File Size') }}</option>
			</select>
		</div>

		<div class="filter-group">
			<label for="order">{{ __('Order') }}</label>
			<select id="order" wire:model.live="sortOrder" class="form-select">
				<option value="desc">{{ __('Descending') }}</option>
				<option value="asc">{{ __('Ascending') }}</option>
			</select>
		</div>

		@if($search || $folderId || $type || $tag)
			<div class="filter-group">
				<button type="button" wire:click="clearFilters" class="btn btn-text">
					{{ __('Clear Filters') }}
				</button>
			</div>
		@endif
	</div>

	{{-- Bulk Actions Bar --}}
	@if($bulkSelectMode && count($selectedMedia) > 0)
		<div class="bulk-actions-bar">
			<div class="selected-count">
				{{ __(':count items selected', ['count' => count($selectedMedia)]) }}
			</div>

			<div class="actions">
				<button type="button" wire:click="selectAll" class="btn btn-sm btn-secondary">
					{{ __('Select All on Page') }}
				</button>

				<button type="button" wire:click="deselectAll" class="btn btn-sm btn-secondary">
					{{ __('Deselect All') }}
				</button>

				<div class="dropdown">
					<button type="button" class="btn btn-sm btn-secondary dropdown-toggle">
						{{ __('Move to Folder') }}
					</button>
					<div class="dropdown-menu">
						<button wire:click="bulkMove(null)" class="dropdown-item">
							{{ __('No Folder') }}
						</button>
						@foreach($this->folders as $folder)
							<button wire:click="bulkMove({{ $folder->id }})" class="dropdown-item">
								{{ $folder->name }}
							</button>
						@endforeach
					</div>
				</div>

				<button
					type="button"
					wire:click="bulkDelete"
					wire:confirm="{{ __('Are you sure you want to delete the selected media?') }}"
					class="btn btn-sm btn-danger"
				>
					<x-icon-fas-trash class="mr-1" />
					{{ __('Delete') }}
				</button>
			</div>
		</div>
	@endif

	{{-- Loading State --}}
	<div wire:loading class="loading-overlay">
		<div class="loading-spinner">
			<div class="spinner"></div>
			<p>{{ __('Loading...') }}</p>
		</div>
	</div>

	{{-- Media Grid --}}
	<div wire:loading.remove>
		@if($this->media->isEmpty())
			{{-- Empty State --}}
			<div class="empty-state">
				<div class="empty-icon">
					<x-icon-fas-images />
				</div>
				<h3>{{ __('No media found') }}</h3>
				@if($search || $type || $folderId)
					<p>{{ __('Try adjusting your filters or search terms') }}</p>
					<button type="button" wire:click="clearFilters" class="btn btn-primary">
						{{ __('Clear Filters') }}
					</button>
				@else
					<p>{{ __('Upload your first media file to get started') }}</p>
					<a href="{{ route('admin.media.add') }}" class="btn btn-primary">
						<x-icon-fas-upload class="mr-2" />
						{{ __('Upload Media') }}
					</a>
				@endif
			</div>
		@else
			{{-- Media Grid Component --}}
			<livewire:media::media-grid
				:media="$this->media"
				:viewMode="$viewMode"
				:bulkSelectMode="$bulkSelectMode"
				:selectedMedia="$selectedMedia"
			/>

			{{-- Pagination --}}
			<div class="pagination-wrapper">
				{{ $this->media->links() }}
			</div>
		@endif
	</div>
</div>
