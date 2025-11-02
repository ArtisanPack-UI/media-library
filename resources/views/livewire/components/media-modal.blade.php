<div
	x-data="{
		handleKeydown(event) {
			if (event.key === 'Escape' && $wire.isOpen) {
				event.preventDefault();
				$wire.close();
			} else if (event.key === 'Enter' && $wire.isOpen && $wire.activeTab === 'library') {
				event.preventDefault();
				$wire.confirmSelection();
			}
		}
	}"
	@keydown.window="handleKeydown($event)"
>
	<x-artisanpack-modal
		wire:model="isOpen"
		:title="$multiSelect ? __('Select Media') : __('Select Media Item')"
		class="w-full media-library-modal"
	>
		<x-artisanpack-tabs wire:model="activeTab">
			<x-artisanpack-tab name="library" :label="__('Media Library')" icon="o-photo">
				{{-- Filters Bar --}}
				<div class="flex gap-3 mb-5 flex-wrap">
					{{-- Search --}}
					<div class="flex-1 min-w-[200px]">
						<x-artisanpack-input
							wire:model.live.debounce.300ms="search"
							:placeholder="__('Search media...')"
							aria-label="{{ __('Search media') }}"
						/>
					</div>

					{{-- Type Filter --}}
					<div class="min-w-[150px]">
						<x-artisanpack-select
							wire:model.live="typeFilter"
							:options="$this->typeFilterOptions"
							option-value="key"
							option-label="label"
							aria-label="{{ __('Filter by type') }}"
						/>
					</div>

					{{-- Folder Filter --}}
					<div class="min-w-[150px]">
						<x-artisanpack-select
							wire:model.live="folderId"
							:options="$this->folderOptions"
							option-value="key"
							option-label="label"
							aria-label="{{ __('Filter by folder') }}"
						/>
					</div>

					{{-- Clear Filters --}}
					@if ($search || $folderId || $typeFilter)
						<x-artisanpack-button
							wire:click="resetFilters"
							type="button"
							variant="ghost"
							size="sm"
						>
							{{ __('Clear Filters') }}
						</x-artisanpack-button>
					@endif
				</div>

				{{-- Selected Count --}}
				@if (count($selectedMedia) > 0)
					<x-artisanpack-alert variant="info" class="mb-4">
						<div class="flex items-center justify-between">
							<span class="text-sm font-medium">
								{{ count($selectedMedia) }} {{ count($selectedMedia) === 1 ? __('item selected') : __('items selected') }}
								@if ($maxSelections > 0)
									<span class="text-zinc-600 dark:text-zinc-400 font-normal">
										({{ __('max :count', ['count' => $maxSelections]) }})
									</span>
								@endif
							</span>

							<x-artisanpack-button
								wire:click="clearSelections"
								type="button"
								variant="outline"
								size="sm"
							>
								{{ __('Clear') }}
							</x-artisanpack-button>
						</div>
					</x-artisanpack-alert>
				@endif

				{{-- Media Grid --}}
				<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 mb-5">
					@forelse ($this->media as $mediaItem)
						<div
							wire:key="modal-media-{{ $mediaItem->id }}"
							wire:click="toggleSelect({{ $mediaItem->id }})"
							tabindex="0"
							role="button"
							aria-pressed="{{ in_array($mediaItem->id, $selectedMedia, true) ? 'true' : 'false' }}"
							@class([
								'relative cursor-pointer rounded-lg overflow-hidden transition-all',
								'border-2 hover:shadow-md',
								'border-primary bg-primary/10' => in_array($mediaItem->id, $selectedMedia, true),
								'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-500' => !in_array($mediaItem->id, $selectedMedia, true),
							])
							@keydown.enter="$wire.toggleSelect({{ $mediaItem->id }})"
							@keydown.space.prevent="$wire.toggleSelect({{ $mediaItem->id }})"
						>
							{{-- Selection Indicator --}}
							@if (in_array($mediaItem->id, $selectedMedia, true))
								<div
									class="absolute top-2 right-2 w-6 h-6 bg-primary rounded-full flex items-center justify-center z-10">
									<x-artisanpack-icon name="fas.check" class="w-4 h-4 text-white"/>
								</div>
							@endif

							{{-- Media Preview --}}
							<div
								class="w-full aspect-square bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center overflow-hidden">
								@if ($mediaItem->isImage())
									<img
										src="{{ $mediaItem->imageUrl('thumbnail') }}"
										alt="{{ $mediaItem->alt_text ?? $mediaItem->file_name }}"
										loading="lazy"
										class="w-full h-full object-cover"
									/>
								@else
									@if ($mediaItem->isVideo())
										<x-artisanpack-icon name="fas.video" class="w-12 h-12 text-zinc-400"/>
									@elseif ($mediaItem->isAudio())
										<x-artisanpack-icon name="fas.music" class="w-12 h-12 text-zinc-400"/>
									@else
										<x-artisanpack-icon name="fas.file" class="w-12 h-12 text-zinc-400"/>
									@endif
								@endif
							</div>

							{{-- Media Info --}}
							<div class="p-2">
								<div
									class="text-sm text-zinc-900 dark:text-white font-medium truncate"
									title="{{ $mediaItem->title ?? $mediaItem->file_name }}"
								>
									{{ $mediaItem->title ?? $mediaItem->file_name }}
								</div>
								<div class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
									{{ $mediaItem->humanFileSize() }}
								</div>
							</div>
						</div>
					@empty
						<div class="col-span-full text-center py-12 text-zinc-600 dark:text-zinc-400">
							<x-artisanpack-icon name="fas.images" class="w-12 h-12 mx-auto mb-4 opacity-50"/>
							<p class="text-base font-medium mb-2">{{ __('No media found') }}</p>
							<p class="text-sm">
								@if ($search || $folderId || $typeFilter)
									{{ __('Try adjusting your filters') }}
								@else
									{{ __('Upload some media to get started') }}
								@endif
							</p>
						</div>
					@endforelse
				</div>

				{{-- Pagination --}}
				@if ($this->media->hasPages())
					<div class="mt-5">
						{{ $this->media->links() }}
					</div>
				@endif
			</x-artisanpack-tab>

			<x-artisanpack-tab name="upload" :label="__('Upload New')" icon="o-cloud-arrow-up">
				<livewire:media::media-upload/>
			</x-artisanpack-tab>
		</x-artisanpack-tabs>

		<x-slot:actions>
			<div x-show="$wire.activeTab === 'library'">
				<x-artisanpack-button
					wire:click="close"
					type="button"
					variant="secondary"
				>
					{{ __('Cancel') }}
				</x-artisanpack-button>

				<x-artisanpack-button
					wire:click="confirmSelection"
					type="button"
					:disabled="count($selectedMedia) === 0"
					variant="primary"
				>
					{{ __('Select') }}
					@if (count($selectedMedia) > 0)
						({{ count($selectedMedia) }})
					@endif
				</x-artisanpack-button>
			</div>
		</x-slot:actions>
	</x-artisanpack-modal>
</div>
