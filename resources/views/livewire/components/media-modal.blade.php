<div
	x-data="{
		isDragging: false,
		focusedIndex: $wire.entangle('focusedIndex'),
		previouslyFocused: null,

		init() {
			// Watch for modal open/close to manage focus
			this.$watch('$wire.isOpen', (isOpen) => {
				if (isOpen) {
					// Store the previously focused element
					this.previouslyFocused = document.activeElement;

					// Focus first focusable element after modal opens
					this.$nextTick(() => {
						const firstFocusable = this.$root.querySelector(
							'button, [href], input, select, textarea, [tabindex]:not([tabindex=\"-1\"])'
						);
						if (firstFocusable) {
							firstFocusable.focus();
						}
					});
				} else {
					// Restore focus to previously focused element
					if (this.previouslyFocused && typeof this.previouslyFocused.focus === 'function') {
						this.$nextTick(() => {
							this.previouslyFocused.focus();
							this.previouslyFocused = null;
						});
					}
				}
			});
		},

		handleKeydown(event) {
			if (!$wire.isOpen) return;

			// Escape to close
			if (event.key === 'Escape') {
				event.preventDefault();
				$wire.close();
				return;
			}

			// Only handle navigation keys when on library tab
			if ($wire.activeTab !== 'library') return;

			// Enter to confirm selection
			if (event.key === 'Enter' && !event.target.matches('input, textarea, select')) {
				event.preventDefault();
				if (this.focusedIndex >= 0) {
					$wire.selectFocused();
				} else {
					$wire.confirmSelection();
				}
				return;
			}

			// Space to toggle selection on focused item
			if (event.key === ' ' && !event.target.matches('input, textarea, select')) {
				event.preventDefault();
				if (this.focusedIndex >= 0) {
					$wire.selectFocused();
				}
				return;
			}

			// Home to go to first item
			if (event.key === 'Home' && !event.target.matches('input, textarea, select')) {
				event.preventDefault();
				$wire.focusFirst();
				this.scrollToFocused();
				return;
			}

			// End to go to last item
			if (event.key === 'End' && !event.target.matches('input, textarea, select')) {
				event.preventDefault();
				$wire.focusLast();
				this.scrollToFocused();
				return;
			}

			// Arrow key navigation
			const columnsPerRow = this.getColumnsPerRow();

			switch (event.key) {
				case 'ArrowRight':
					event.preventDefault();
					$wire.focusNext();
					this.scrollToFocused();
					break;
				case 'ArrowLeft':
					event.preventDefault();
					$wire.focusPrevious();
					this.scrollToFocused();
					break;
				case 'ArrowDown':
					event.preventDefault();
					$wire.focusDown(columnsPerRow);
					this.scrollToFocused();
					break;
				case 'ArrowUp':
					event.preventDefault();
					$wire.focusUp(columnsPerRow);
					this.scrollToFocused();
					break;
			}
		},

		getColumnsPerRow() {
			// Determine columns based on screen width and inline mode
			const width = window.innerWidth;
			if ($wire.inlineMode) {
				if (width >= 1024) return 4;
				if (width >= 768) return 3;
				return 2;
			}
			if (width >= 1280) return 5;
			if (width >= 1024) return 4;
			if (width >= 768) return 3;
			return 2;
		},

		scrollToFocused() {
			this.$nextTick(() => {
				const focused = this.$root.querySelector(`[data-focused='true']`);
				if (focused) {
					focused.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
					focused.focus();
				}
			});
		},

		handleDragOver(event) {
			event.preventDefault();
			this.isDragging = true;
		},

		handleDragLeave(event) {
			event.preventDefault();
			this.isDragging = false;
		},

		handleDrop(event) {
			event.preventDefault();
			this.isDragging = false;

			const files = event.dataTransfer.files;
			if (files.length > 0) {
				// Switch to upload tab and trigger upload
				$wire.switchTab('upload');
				// Dispatch event for the upload component to handle
				this.$dispatch('files-dropped', { files: Array.from(files) });
			}
		}
	}"
	@keydown.window="handleKeydown($event)"
>
	<x-artisanpack-modal
		wire:model="isOpen"
		:title="$multiSelect ? __('Select Media') : __('Select Media Item')"
		@class([
			'w-full media-library-modal',
			'media-library-modal--inline max-w-2xl' => $inlineMode,
		])
		:size="$inlineMode ? 'md' : 'xl'"
	>
		<x-artisanpack-tabs wire:model="activeTab">
			<x-artisanpack-tab name="library" :label="__('Media Library')" icon="o-photo">
				{{-- Drag and Drop Overlay --}}
				<div
					x-show="isDragging"
					x-transition:enter="transition ease-out duration-200"
					x-transition:enter-start="opacity-0"
					x-transition:enter-end="opacity-100"
					x-transition:leave="transition ease-in duration-150"
					x-transition:leave-start="opacity-100"
					x-transition:leave-end="opacity-0"
					class="absolute inset-0 z-50 flex items-center justify-center bg-primary/20 border-4 border-dashed border-primary rounded-lg"
					@dragover.prevent="isDragging = true"
					@dragleave.prevent="isDragging = false"
					@drop="handleDrop($event)"
				>
					<div class="text-center">
						<x-artisanpack-icon name="fas.cloud-arrow-up" class="w-16 h-16 mx-auto mb-4 text-primary"/>
						<p class="text-lg font-semibold text-primary">{{ __('Drop files to upload') }}</p>
					</div>
				</div>

				{{-- Main Content Area (with drag events) --}}
				<div
					class="relative"
					@dragover="handleDragOver($event)"
					@dragleave="handleDragLeave($event)"
					@drop="handleDrop($event)"
				>
					{{-- Filters Bar --}}
					<div @class([
						'flex gap-3 mb-5 flex-wrap',
						'gap-2 mb-3' => $inlineMode,
					])>
						{{-- Search --}}
						<div @class([
							'flex-1 min-w-[200px]',
							'min-w-[150px]' => $inlineMode,
						])>
							<x-artisanpack-input
								wire:model.live.debounce.300ms="search"
								:placeholder="__('Search media...')"
								aria-label="{{ __('Search media') }}"
								:size="$inlineMode ? 'sm' : 'md'"
							/>
						</div>

						{{-- Type Filter --}}
						<div @class([
							'min-w-[150px]',
							'min-w-[120px]' => $inlineMode,
						])>
							<x-artisanpack-select
								wire:model.live="typeFilter"
								:options="$this->typeFilterOptions"
								option-value="key"
								option-label="label"
								aria-label="{{ __('Filter by type') }}"
								:size="$inlineMode ? 'sm' : 'md'"
							/>
						</div>

						{{-- Folder Filter (hidden in inline mode) --}}
						@if (!$inlineMode)
							<div class="min-w-[150px]">
								<x-artisanpack-select
									wire:model.live="folderId"
									:options="$this->folderOptions"
									option-value="key"
									option-label="label"
									aria-label="{{ __('Filter by folder') }}"
								/>
							</div>
						@endif

						{{-- Clear Filters --}}
						@if ($search || $folderId || $typeFilter)
							<x-artisanpack-button
								wire:click="resetFilters"
								type="button"
								variant="ghost"
								:size="$inlineMode ? 'xs' : 'sm'"
							>
								{{ __('Clear') }}
							</x-artisanpack-button>
						@endif
					</div>

					{{-- Recently Used Section --}}
					@if (!empty($recentlyUsed) && $this->recentlyUsedMedia->isNotEmpty() && empty($search) && !$folderId && !$typeFilter)
						<div class="mb-5">
							<h4 @class([
								'text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-3',
								'text-xs mb-2' => $inlineMode,
							])>
								<x-artisanpack-icon name="fas.clock-rotate-left" @class(['w-4 h-4 inline-block mr-1', 'w-3 h-3' => $inlineMode])/>
								{{ __('Recently Used') }}
							</h4>
							<div @class([
								'flex gap-3 overflow-x-auto pb-2',
								'gap-2' => $inlineMode,
							])>
								@foreach ($this->recentlyUsedMedia->take(5) as $recentMedia)
									<div
										wire:key="recent-media-{{ $recentMedia->id }}"
										wire:click="toggleSelect({{ $recentMedia->id }})"
										tabindex="0"
										role="button"
										aria-pressed="{{ in_array($recentMedia->id, $selectedMedia, true) ? 'true' : 'false' }}"
										@class([
											'relative cursor-pointer rounded-lg overflow-hidden transition-all flex-shrink-0',
											'border-2 hover:shadow-md',
											'w-20 h-20' => $inlineMode,
											'w-24 h-24' => !$inlineMode,
											'border-primary bg-primary/10' => in_array($recentMedia->id, $selectedMedia, true),
											'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-500' => !in_array($recentMedia->id, $selectedMedia, true),
										])
										@keydown.enter="$wire.toggleSelect({{ $recentMedia->id }})"
										@keydown.space.prevent="$wire.toggleSelect({{ $recentMedia->id }})"
									>
										@if (in_array($recentMedia->id, $selectedMedia, true))
											<div @class([
												'absolute top-1 right-1 bg-primary rounded-full flex items-center justify-center z-10',
												'w-4 h-4' => $inlineMode,
												'w-5 h-5' => !$inlineMode,
											])>
												<x-artisanpack-icon name="fas.check" @class(['text-white', 'w-2 h-2' => $inlineMode, 'w-3 h-3' => !$inlineMode])/>
											</div>
										@endif
										<div class="w-full h-full bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center overflow-hidden">
											@if ($recentMedia->isImage())
												<img
													src="{{ $recentMedia->imageUrl('thumbnail') }}"
													alt="{{ $recentMedia->alt_text ?? $recentMedia->file_name }}"
													loading="lazy"
													class="w-full h-full object-cover"
												/>
											@else
												@if ($recentMedia->isVideo())
													<x-artisanpack-icon name="fas.video" class="w-8 h-8 text-zinc-400"/>
												@elseif ($recentMedia->isAudio())
													<x-artisanpack-icon name="fas.music" class="w-8 h-8 text-zinc-400"/>
												@else
													<x-artisanpack-icon name="fas.file" class="w-8 h-8 text-zinc-400"/>
												@endif
											@endif
										</div>
									</div>
								@endforeach
							</div>
						</div>
					@endif

					{{-- Selected Count --}}
					@if (count($selectedMedia) > 0)
						<x-artisanpack-alert variant="info" @class(['mb-4', 'mb-3 py-2' => $inlineMode])>
							<div class="flex items-center justify-between">
								<span @class(['text-sm font-medium', 'text-xs' => $inlineMode])>
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
									:size="$inlineMode ? 'xs' : 'sm'"
								>
									{{ __('Clear') }}
								</x-artisanpack-button>
							</div>
						</x-artisanpack-alert>
					@endif

					{{-- Keyboard Navigation Hint --}}
					@if (!$inlineMode)
						<div class="mb-3 text-xs text-zinc-500 dark:text-zinc-400">
							<x-artisanpack-icon name="fas.keyboard" class="w-3 h-3 inline-block mr-1"/>
							{{ __('Use arrow keys to navigate, Space/Enter to select, Home/End for first/last') }}
						</div>
					@endif

					{{-- Media Grid --}}
					<div
						@class([
							'grid gap-4 mb-5',
							'grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5' => !$inlineMode,
							'grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2 mb-3' => $inlineMode,
						])
						role="grid"
						aria-label="{{ __('Media items') }}"
					>
						@forelse ($this->media as $index => $mediaItem)
							@php
								$isFocused = $focusedIndex === $index;
								$isSelected = in_array($mediaItem->id, $selectedMedia, true);
							@endphp
							<div
								wire:key="modal-media-{{ $mediaItem->id }}"
								wire:click="toggleSelect({{ $mediaItem->id }})"
								tabindex="{{ $isFocused || ($focusedIndex === -1 && $loop->first) ? '0' : '-1' }}"
								role="gridcell"
								aria-selected="{{ $isSelected ? 'true' : 'false' }}"
								aria-label="{{ $mediaItem->title ?? $mediaItem->file_name }} - {{ $mediaItem->humanFileSize() }}{{ $isSelected ? ' - ' . __('Selected') : '' }}"
								data-index="{{ $index }}"
								data-focused="{{ $isFocused ? 'true' : 'false' }}"
								@class([
									'relative cursor-pointer rounded-lg overflow-hidden transition-all focus:outline-none',
									'border-2 hover:shadow-md',
									'border-primary bg-primary/10' => $isSelected,
									'ring-2 ring-offset-2 ring-primary dark:ring-offset-zinc-800' => $isFocused && !$isSelected,
									'border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 hover:border-zinc-400 dark:hover:border-zinc-500' => !$isSelected && !$isFocused,
								])
								@focus="$wire.set('focusedIndex', {{ $index }})"
							>
								{{-- Selection Indicator --}}
								@if ($isSelected)
									<div @class([
										'absolute top-2 right-2 bg-primary rounded-full flex items-center justify-center z-10',
										'w-5 h-5' => $inlineMode,
										'w-6 h-6' => !$inlineMode,
									])>
										<x-artisanpack-icon name="fas.check" @class(['text-white', 'w-3 h-3' => $inlineMode, 'w-4 h-4' => !$inlineMode])/>
									</div>
								@endif

								{{-- Media Preview --}}
								<div @class([
									'w-full bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center overflow-hidden',
									'aspect-square' => true,
								])>
									@if ($mediaItem->isImage())
										<img
											src="{{ $mediaItem->imageUrl('thumbnail') }}"
											alt="{{ $mediaItem->alt_text ?? $mediaItem->file_name }}"
											loading="lazy"
											class="w-full h-full object-cover"
										/>
									@else
										@if ($mediaItem->isVideo())
											<x-artisanpack-icon name="fas.video" @class(['text-zinc-400', 'w-8 h-8' => $inlineMode, 'w-12 h-12' => !$inlineMode])/>
										@elseif ($mediaItem->isAudio())
											<x-artisanpack-icon name="fas.music" @class(['text-zinc-400', 'w-8 h-8' => $inlineMode, 'w-12 h-12' => !$inlineMode])/>
										@else
											<x-artisanpack-icon name="fas.file" @class(['text-zinc-400', 'w-8 h-8' => $inlineMode, 'w-12 h-12' => !$inlineMode])/>
										@endif
									@endif
								</div>

								{{-- Media Info --}}
								<div @class(['p-2', 'p-1.5' => $inlineMode])>
									<div
										@class([
											'text-zinc-900 dark:text-white font-medium truncate',
											'text-xs' => $inlineMode,
											'text-sm' => !$inlineMode,
										])
										title="{{ $mediaItem->title ?? $mediaItem->file_name }}"
									>
										{{ $mediaItem->title ?? $mediaItem->file_name }}
									</div>
									@if (!$inlineMode)
										<div class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
											{{ $mediaItem->humanFileSize() }}
										</div>
									@endif
								</div>
							</div>
						@empty
							<div class="col-span-full text-center py-12 text-zinc-600 dark:text-zinc-400">
								<x-artisanpack-icon name="fas.images" @class(['mx-auto mb-4 opacity-50', 'w-8 h-8' => $inlineMode, 'w-12 h-12' => !$inlineMode])/>
								<p @class(['font-medium mb-2', 'text-sm' => $inlineMode, 'text-base' => !$inlineMode])>{{ __('No media found') }}</p>
								<p @class(['text-xs' => $inlineMode, 'text-sm' => !$inlineMode])>
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
						<div @class(['mt-5', 'mt-3' => $inlineMode])>
							{{ $this->media->links() }}
						</div>
					@endif
				</div>
			</x-artisanpack-tab>

			<x-artisanpack-tab name="upload" :label="__('Upload New')" icon="o-cloud-arrow-up">
				{{-- Drag and Drop Zone for Upload Tab --}}
				<div
					class="relative"
					@dragover="handleDragOver($event)"
					@dragleave="handleDragLeave($event)"
					@drop="handleDrop($event)"
				>
					<div
						x-show="isDragging"
						x-transition
						class="absolute inset-0 z-50 flex items-center justify-center bg-primary/20 border-4 border-dashed border-primary rounded-lg"
					>
						<div class="text-center">
							<x-artisanpack-icon name="fas.cloud-arrow-up" class="w-16 h-16 mx-auto mb-4 text-primary"/>
							<p class="text-lg font-semibold text-primary">{{ __('Drop files to upload') }}</p>
						</div>
					</div>

					<livewire:media::media-upload/>
				</div>
			</x-artisanpack-tab>
		</x-artisanpack-tabs>

		<x-slot:actions>
			<div x-show="$wire.activeTab === 'library'">
				<x-artisanpack-button
					wire:click="close"
					type="button"
					variant="secondary"
					:size="$inlineMode ? 'sm' : 'md'"
				>
					{{ __('Cancel') }}
				</x-artisanpack-button>

				<x-artisanpack-button
					wire:click="confirmSelection"
					type="button"
					:disabled="count($selectedMedia) === 0"
					variant="primary"
					:size="$inlineMode ? 'sm' : 'md'"
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
