<div class="media-library-container space-y-6">
	{{-- Header --}}
	<div class="flex items-center justify-between">
		<div class="flex-1">
			<x-artisanpack-heading level="1">{{ __('Media Library') }}</x-artisanpack-heading>

			@if($this->currentFolder)
				<x-artisanpack-breadcrumbs class="mt-2">
					<x-artisanpack-link href="#" wire:click.prevent="setFolder(null)">
						{{ __('All Media') }}
					</x-artisanpack-link>
					<x-artisanpack-link>{{ $this->currentFolder->name }}</x-artisanpack-link>
				</x-artisanpack-breadcrumbs>
			@endif
		</div>

		<div class="flex items-center gap-2">
			<x-artisanpack-button
				wire:click="toggleViewMode"
				type="button"
				variant="secondary"
				size="sm"
				:title="__('Toggle View')"
			>
				@if($viewMode === 'grid')
					<x-artisanpack-icon name="fas.list" />
				@else
					<x-artisanpack-icon name="fas.th" />
				@endif
			</x-artisanpack-button>

			<x-artisanpack-button
				wire:click="toggleBulkSelect"
				type="button"
				variant="secondary"
				size="sm"
			>
				@if($bulkSelectMode)
					{{ __('Cancel Selection') }}
				@else
					{{ __('Select Multiple') }}
				@endif
			</x-artisanpack-button>

			<x-artisanpack-button
				:href="route('admin.media.add')"
				variant="primary"
				size="sm"
			>
				<x-artisanpack-icon name="fas.upload" class="mr-2" />
				{{ __('Upload Media') }}
			</x-artisanpack-button>
		</div>
	</div>

	{{-- Filters Bar --}}
	<x-artisanpack-card>
		<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
			<x-artisanpack-input
				wire:model.live.debounce.300ms="search"
				:label="__('Search')"
				:placeholder="__('Search media...')"
			/>

			<x-artisanpack-select
				wire:model.live="type"
				:label="__('Type')"
			>
				<option value="">{{ __('All Types') }}</option>
				<option value="image">{{ __('Images') }}</option>
				<option value="video">{{ __('Videos') }}</option>
				<option value="audio">{{ __('Audio') }}</option>
				<option value="document">{{ __('Documents') }}</option>
			</x-artisanpack-select>

			<x-artisanpack-select
				wire:model.live="folderId"
				:label="__('Folder')"
			>
				<option value="">{{ __('All Folders') }}</option>
				@foreach($this->folders as $folder)
					<option value="{{ $folder->id }}">{{ $folder->name }}</option>
					@if($folder->children->isNotEmpty())
						@foreach($folder->children as $child)
							<option value="{{ $child->id }}">-- {{ $child->name }}</option>
						@endforeach
					@endif
				@endforeach
			</x-artisanpack-select>

			<x-artisanpack-select
				wire:model.live="sortBy"
				:label="__('Sort By')"
			>
				<option value="created_at">{{ __('Date Added') }}</option>
				<option value="title">{{ __('Title') }}</option>
				<option value="file_name">{{ __('File Name') }}</option>
				<option value="file_size">{{ __('File Size') }}</option>
			</x-artisanpack-select>

			<x-artisanpack-select
				wire:model.live="sortOrder"
				:label="__('Order')"
			>
				<option value="desc">{{ __('Descending') }}</option>
				<option value="asc">{{ __('Ascending') }}</option>
			</x-artisanpack-select>
		</div>

		@if($search || $folderId || $type || $tag)
			<div class="mt-4">
				<x-artisanpack-button
					wire:click="clearFilters"
					type="button"
					variant="ghost"
					size="sm"
				>
					{{ __('Clear Filters') }}
				</x-artisanpack-button>
			</div>
		@endif
	</x-artisanpack-card>

	{{-- Bulk Actions Bar --}}
	@if($bulkSelectMode && count($selectedMedia) > 0)
		<x-artisanpack-card>
			<div class="flex items-center justify-between">
				<div class="text-sm font-medium">
					{{ __(':count items selected', ['count' => count($selectedMedia)]) }}
				</div>

				<div class="flex items-center gap-2">
					<x-artisanpack-button
						wire:click="selectAll"
						type="button"
						variant="secondary"
						size="sm"
					>
						{{ __('Select All on Page') }}
					</x-artisanpack-button>

					<x-artisanpack-button
						wire:click="deselectAll"
						type="button"
						variant="secondary"
						size="sm"
					>
						{{ __('Deselect All') }}
					</x-artisanpack-button>

					<x-artisanpack-dropdown>
						<x-slot:trigger>
							<x-artisanpack-button variant="secondary" size="sm">
								{{ __('Move to Folder') }}
								<x-artisanpack-icon name="fas.chevron-down" class="ml-2" />
							</x-artisanpack-button>
						</x-slot:trigger>

						<x-artisanpack-menu-item wire:click="bulkMove(null)">
							{{ __('No Folder') }}
						</x-artisanpack-menu-item>

						@foreach($this->folders as $folder)
							<x-artisanpack-menu-item wire:click="bulkMove({{ $folder->id }})">
								{{ $folder->name }}
							</x-artisanpack-menu-item>
						@endforeach
					</x-artisanpack-dropdown>

					<x-artisanpack-button
						wire:click="bulkDelete"
						wire:confirm="{{ __('Are you sure you want to delete the selected media?') }}"
						type="button"
						variant="danger"
						size="sm"
					>
						<x-artisanpack-icon name="fas.trash" class="mr-1" />
						{{ __('Delete') }}
					</x-artisanpack-button>
				</div>
			</div>
		</x-artisanpack-card>
	@endif

	{{-- Loading State --}}
	<div wire:loading class="flex items-center justify-center py-12">
		<x-artisanpack-loading class="w-8 h-8" />
		<span class="ml-2">{{ __('Loading...') }}</span>
	</div>

	{{-- Media Grid --}}
	<div wire:loading.remove>
		@if($this->media->isEmpty())
			{{-- Empty State --}}
			<x-artisanpack-card class="text-center py-12">
				<x-artisanpack-icon name="fas.images" class="w-16 h-16 mx-auto text-zinc-400 dark:text-zinc-600 mb-4" />
				<x-artisanpack-heading level="3" class="mb-2">{{ __('No media found') }}</x-artisanpack-heading>

				@if($search || $type || $folderId)
					<p class="text-zinc-600 dark:text-zinc-400 mb-4">{{ __('Try adjusting your filters or search terms') }}</p>
					<x-artisanpack-button wire:click="clearFilters" variant="primary">
						{{ __('Clear Filters') }}
					</x-artisanpack-button>
				@else
					<p class="text-zinc-600 dark:text-zinc-400 mb-4">{{ __('Upload your first media file to get started') }}</p>
					<x-artisanpack-button :href="route('admin.media.add')" variant="primary">
						<x-artisanpack-icon name="fas.upload" class="mr-2" />
						{{ __('Upload Media') }}
					</x-artisanpack-button>
				@endif
			</x-artisanpack-card>
		@else
			{{-- Media Grid Component --}}
			<livewire:media::media-grid
				:media="$this->media"
				:viewMode="$viewMode"
				:bulkSelectMode="$bulkSelectMode"
				:selectedMedia="$selectedMedia"
			/>

			{{-- Pagination --}}
			<div class="mt-6">
				{{ $this->media->links() }}
			</div>
		@endif
	</div>
</div>
