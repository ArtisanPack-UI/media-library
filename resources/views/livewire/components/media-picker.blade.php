<div
	x-data="{
		focusedIndex: @entangle('focusedIndex'),

		async handleKeydown(event) {
			if (!$wire.isOpen) return;

			// Escape to close
			if (event.key === 'Escape') {
				event.preventDefault();
				$wire.close();
				return;
			}

			// Only handle navigation keys when focused on the grid area
			const gridArea = this.$root.querySelector('[role=grid]');
			if (!gridArea || !gridArea.contains(event.target)) {
				// Allow navigation if target is a gridcell
				if (!event.target.closest('[role=gridcell]')) return;
			}

			// Enter to toggle selection on focused item
			if (event.key === 'Enter' && !event.target.matches('input, textarea, select')) {
				event.preventDefault();
				if (this.focusedIndex >= 0) {
					$wire.selectFocused();
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
			if (event.key === 'Home') {
				event.preventDefault();
				await $wire.focusFirst();
				this.scrollToFocused();
				return;
			}

			// End to go to last item
			if (event.key === 'End') {
				event.preventDefault();
				await $wire.focusLast();
				this.scrollToFocused();
				return;
			}

			// Arrow key navigation
			const columnsPerRow = this.getColumnsPerRow();

			switch (event.key) {
				case 'ArrowRight':
					event.preventDefault();
					await $wire.focusNext();
					this.scrollToFocused();
					break;
				case 'ArrowLeft':
					event.preventDefault();
					await $wire.focusPrevious();
					this.scrollToFocused();
					break;
				case 'ArrowDown':
					event.preventDefault();
					await $wire.focusDown(columnsPerRow);
					this.scrollToFocused();
					break;
				case 'ArrowUp':
					event.preventDefault();
					await $wire.focusUp(columnsPerRow);
					this.scrollToFocused();
					break;
			}
		},

		getColumnsPerRow() {
			const width = window.innerWidth;
			if (width >= 1024) return 5;
			if (width >= 768) return 4;
			if (width >= 640) return 3;
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
		}
	}"
	@keydown.window="handleKeydown($event)"
>
	@if ($isOpen)
		<div class="media-picker bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg overflow-hidden">
			{{-- Header --}}
			<div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
				<h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
					{{ $multiSelect ? __('Select Media') : __('Select Media Item') }}
				</h3>

				<x-artisanpack-button
					wire:click="close"
					type="button"
					variant="ghost"
					size="sm"
					aria-label="{{ __('Close picker') }}"
				>
					<x-artisanpack-icon name="fas.xmark" class="w-5 h-5"/>
				</x-artisanpack-button>
			</div>

			{{-- Filters Bar --}}
			<div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
				<div class="flex gap-3 flex-wrap">
					{{-- Search --}}
					<div class="flex-1 min-w-[200px]">
						<x-artisanpack-input
							wire:model.live.debounce.300ms="search"
							:placeholder="__('Search media...')"
							aria-label="{{ __('Search media') }}"
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
					@if ($search || $folderId)
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

				{{-- Type Filter Info --}}
				@if ($acceptTypes)
					<div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
						{{ __('Showing: :types', ['types' => $acceptTypes]) }}
					</div>
				@endif
			</div>

			{{-- Selected Count (Multi-select only) --}}
			@if ($multiSelect && count($selectedMedia) > 0)
				<div class="px-4 py-2 bg-primary/10 border-b border-primary/20">
					<div class="flex items-center justify-between">
						<span class="text-sm font-medium text-primary">
							{{ count($selectedMedia) }} {{ count($selectedMedia) === 1 ? __('item selected') : __('items selected') }}
							@if ($maxSelections > 0)
								<span class="text-zinc-600 dark:text-zinc-400 font-normal">
									({{ __('max :count', ['count' => $maxSelections]) }})
								</span>
							@endif
						</span>

						<div class="flex items-center gap-2">
							<x-artisanpack-button
								wire:click="clearSelections"
								type="button"
								variant="ghost"
								size="sm"
							>
								{{ __('Clear') }}
							</x-artisanpack-button>

							<x-artisanpack-button
								wire:click="confirmSelection"
								type="button"
								variant="primary"
								size="sm"
							>
								{{ __('Confirm Selection') }}
							</x-artisanpack-button>
						</div>
					</div>
				</div>
			@endif

			{{-- Keyboard Navigation Hint --}}
			<div class="px-4 pt-2 text-xs text-zinc-500 dark:text-zinc-400">
				<x-artisanpack-icon name="fas.keyboard" class="w-3 h-3 inline-block mr-1"/>
				{{ __('Use arrow keys to navigate, Space/Enter to select, Home/End for first/last') }}
			</div>

			{{-- Media Grid with Infinite Scroll --}}
			<div
				class="p-4 max-h-[400px] overflow-y-auto"
				x-ref="scrollContainer"
				x-data="{
					observer: null,

					observe() {
						// Disconnect existing observer before creating a new one
						if (this.observer) {
							this.observer.disconnect();
						}

						this.observer = new IntersectionObserver((entries) => {
							entries.forEach(entry => {
								if (entry.isIntersecting && $wire.hasMore) {
									$wire.loadMore();
								}
							});
						}, {
							root: this.$refs.scrollContainer,
							rootMargin: '100px'
						});

						let sentinel = this.$refs.sentinel;
						if (sentinel) {
							this.observer.observe(sentinel);
						}
					},

					reconnectObserver() {
						// Re-observe the sentinel after Livewire updates
						this.$nextTick(() => {
							let sentinel = this.$refs.sentinel;
							if (sentinel && this.observer) {
								this.observer.disconnect();
								this.observer.observe(sentinel);
							}
						});
					}
				}"
				x-init="observe()"
				x-on:livewire:updated.window="reconnectObserver()"
			>
				<div
					class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3"
					role="grid"
					aria-label="{{ __('Media items') }}"
					aria-multiselectable="{{ $multiSelect ? 'true' : 'false' }}"
				>
					@forelse ($this->media as $index => $mediaItem)
						@php
							$isFocused = $focusedIndex === $index;
							$isSelected = in_array($mediaItem->id, $selectedMedia, true);
						@endphp
						<div
							wire:key="picker-media-{{ $mediaItem->id }}"
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
							@if (in_array($mediaItem->id, $selectedMedia, true))
								<div class="absolute top-2 right-2 w-5 h-5 bg-primary rounded-full flex items-center justify-center z-10">
									<x-artisanpack-icon name="fas.check" class="w-3 h-3 text-white"/>
								</div>
							@endif

							{{-- Media Preview --}}
							<div class="w-full aspect-square bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center overflow-hidden">
								@if ($mediaItem->isImage())
									<img
										src="{{ $mediaItem->imageUrl('thumbnail') }}"
										alt="{{ $mediaItem->alt_text ?? $mediaItem->file_name }}"
										loading="lazy"
										class="w-full h-full object-cover"
									/>
								@else
									@if ($mediaItem->isVideo())
										<x-artisanpack-icon name="fas.video" class="w-8 h-8 text-zinc-400"/>
									@elseif ($mediaItem->isAudio())
										<x-artisanpack-icon name="fas.music" class="w-8 h-8 text-zinc-400"/>
									@else
										<x-artisanpack-icon name="fas.file" class="w-8 h-8 text-zinc-400"/>
									@endif
								@endif
							</div>

							{{-- Media Info --}}
							<div class="p-2">
								<div
									class="text-xs text-zinc-900 dark:text-white font-medium truncate"
									title="{{ $mediaItem->title ?? $mediaItem->file_name }}"
								>
									{{ $mediaItem->title ?? $mediaItem->file_name }}
								</div>
								<div class="text-xs text-zinc-500 dark:text-zinc-400">
									{{ $mediaItem->humanFileSize() }}
								</div>
							</div>
						</div>
					@empty
						<div class="col-span-full text-center py-8 text-zinc-500 dark:text-zinc-400">
							<x-artisanpack-icon name="fas.images" class="w-10 h-10 mx-auto mb-3 opacity-50"/>
							<p class="text-sm font-medium mb-1">{{ __('No media found') }}</p>
							<p class="text-xs">
								@if ($search || $folderId || $acceptTypes)
									{{ __('Try adjusting your filters') }}
								@else
									{{ __('Upload some media to get started') }}
								@endif
							</p>
						</div>
					@endforelse
				</div>

				{{-- Infinite Scroll Sentinel --}}
				@if ($this->hasMore)
					<div
						x-ref="sentinel"
						wire:loading.remove
						class="flex justify-center py-4"
					>
						<x-artisanpack-loading size="sm"/>
					</div>
				@endif

				{{-- Loading Indicator --}}
				<div wire:loading wire:target="loadMore" class="flex justify-center py-4">
					<x-artisanpack-loading size="sm"/>
				</div>
			</div>

			{{-- Footer --}}
			<div class="p-4 border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
				<div class="flex items-center justify-between text-sm text-zinc-500 dark:text-zinc-400">
					<span>
						{{ __('Showing :count of :total', ['count' => count($this->media), 'total' => $this->totalCount]) }}
					</span>

					<x-artisanpack-button
						wire:click="close"
						type="button"
						variant="secondary"
						size="sm"
					>
						{{ __('Cancel') }}
					</x-artisanpack-button>
				</div>
			</div>
		</div>
	@else
		{{-- Closed State Slot --}}
		<div wire:click="open" class="cursor-pointer">
			{{ $slot ?? '' }}
		</div>
	@endif
</div>
