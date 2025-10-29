<div
	x-data="{
		isOpen: @entangle('isOpen'),
		activeTab: @entangle('activeTab'),
		handleKeydown(event) {
			if (event.key === 'Escape' && this.isOpen) {
				event.preventDefault();
				$wire.close();
			} else if (event.key === 'Enter' && this.isOpen && this.activeTab === 'library') {
				event.preventDefault();
				$wire.confirmSelection();
			}
		}
	}"
	x-init="$watch('isOpen', value => {
		if (value) {
			document.body.style.overflow = 'hidden';
		} else {
			document.body.style.overflow = '';
		}
	})"
	@keydown.window="handleKeydown($event)"
	wire:ignore.self
>
	{{-- Modal Backdrop --}}
	<div
		x-show="isOpen"
		x-transition:enter="transition ease-out duration-300"
		x-transition:enter-start="opacity-0"
		x-transition:enter-end="opacity-100"
		x-transition:leave="transition ease-in duration-200"
		x-transition:leave-start="opacity-100"
		x-transition:leave-end="opacity-0"
		@click="$wire.close()"
		class="media-modal-backdrop"
		style="
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-color: rgba(0, 0, 0, 0.5);
			z-index: 9998;
			display: none;
		"
		x-cloak
		aria-hidden="true"
	></div>

	{{-- Modal Container --}}
	<div
		x-show="isOpen"
		x-transition:enter="transition ease-out duration-300"
		x-transition:enter-start="opacity-0 transform scale-95"
		x-transition:enter-end="opacity-100 transform scale-100"
		x-transition:leave="transition ease-in duration-200"
		x-transition:leave-start="opacity-100 transform scale-100"
		x-transition:leave-end="opacity-0 transform scale-95"
		class="media-modal-container"
		style="
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			z-index: 9999;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 16px;
			overflow-y: auto;
		"
		x-cloak
		role="dialog"
		aria-modal="true"
		aria-labelledby="media-modal-title"
	>
		<div
			@click.stop
			class="media-modal-content"
			style="
				background: white;
				border-radius: 8px;
				box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
				width: 100%;
				max-width: 1200px;
				max-height: 90vh;
				display: flex;
				flex-direction: column;
			"
		>
			{{-- Modal Header --}}
			<div
				class="media-modal-header"
				style="
					display: flex;
					align-items: center;
					justify-content: space-between;
					padding: 20px 24px;
					border-bottom: 1px solid #e5e7eb;
				"
			>
				<h2
					id="media-modal-title"
					style="
						font-size: 20px;
						font-weight: 600;
						color: #111827;
						margin: 0;
					"
				>
					{{ $multiSelect ? __('Select Media') : __('Select Media Item') }}
				</h2>

				<button
					wire:click="close"
					type="button"
					aria-label="{{ __('Close modal') }}"
					style="
						background: none;
						border: none;
						color: #6b7280;
						cursor: pointer;
						padding: 8px;
						border-radius: 4px;
						display: flex;
						align-items: center;
						justify-content: center;
						transition: all 0.2s;
					"
					onmouseover="this.style.backgroundColor='#f3f4f6'; this.style.color='#111827';"
					onmouseout="this.style.backgroundColor='transparent'; this.style.color='#6b7280';"
				>
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px; height: 24px;">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
					</svg>
				</button>
			</div>

			{{-- Tabs --}}
			<div
				class="media-modal-tabs"
				style="
					display: flex;
					border-bottom: 1px solid #e5e7eb;
					background-color: #f9fafb;
					padding: 0 24px;
				"
				role="tablist"
			>
				<button
					wire:click="switchTab('library')"
					:class="{ 'active': activeTab === 'library' }"
					type="button"
					role="tab"
					aria-selected="{{ $activeTab === 'library' ? 'true' : 'false' }}"
					aria-controls="library-tab-panel"
					style="
						padding: 12px 16px;
						font-size: 14px;
						font-weight: 500;
						background: none;
						border: none;
						cursor: pointer;
						color: #6b7280;
						border-bottom: 2px solid transparent;
						transition: all 0.2s;
					"
					:style="activeTab === 'library' ? 'color: #3b82f6; border-bottom-color: #3b82f6;' : ''"
					onmouseover="if (this.classList.contains('active') === false) { this.style.color='#111827'; }"
					onmouseout="if (this.classList.contains('active') === false) { this.style.color='#6b7280'; }"
				>
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px; display: inline-block; margin-right: 8px; vertical-align: middle;">
						<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
					</svg>
					{{ __('Media Library') }}
				</button>

				<button
					wire:click="switchTab('upload')"
					:class="{ 'active': activeTab === 'upload' }"
					type="button"
					role="tab"
					aria-selected="{{ $activeTab === 'upload' ? 'true' : 'false' }}"
					aria-controls="upload-tab-panel"
					style="
						padding: 12px 16px;
						font-size: 14px;
						font-weight: 500;
						background: none;
						border: none;
						cursor: pointer;
						color: #6b7280;
						border-bottom: 2px solid transparent;
						transition: all 0.2s;
					"
					:style="activeTab === 'upload' ? 'color: #3b82f6; border-bottom-color: #3b82f6;' : ''"
					onmouseover="if (this.classList.contains('active') === false) { this.style.color='#111827'; }"
					onmouseout="if (this.classList.contains('active') === false) { this.style.color='#6b7280'; }"
				>
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px; display: inline-block; margin-right: 8px; vertical-align: middle;">
						<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
					</svg>
					{{ __('Upload New') }}
				</button>
			</div>

			{{-- Tab Panels --}}
			<div
				class="media-modal-body"
				style="
					flex: 1;
					overflow-y: auto;
					padding: 24px;
					min-height: 400px;
				"
			>
				{{-- Library Tab --}}
				<div
					x-show="activeTab === 'library'"
					id="library-tab-panel"
					role="tabpanel"
					aria-labelledby="library-tab"
				>
					{{-- Filters Bar --}}
					<div
						class="media-modal-filters"
						style="
							display: flex;
							gap: 12px;
							margin-bottom: 20px;
							flex-wrap: wrap;
						"
					>
						{{-- Search --}}
						<div style="flex: 1; min-width: 200px;">
							<label for="modal-search" class="sr-only">{{ __('Search media') }}</label>
							<input
								type="text"
								id="modal-search"
								wire:model.live.debounce.300ms="search"
								placeholder="{{ __('Search media...') }}"
								style="
									width: 100%;
									padding: 8px 12px;
									border: 1px solid #d1d5db;
									border-radius: 6px;
									font-size: 14px;
									transition: border-color 0.2s;
								"
								onfocus="this.style.borderColor='#3b82f6'; this.style.outline='none';"
								onblur="this.style.borderColor='#d1d5db';"
							/>
						</div>

						{{-- Type Filter --}}
						<div style="min-width: 150px;">
							<label for="modal-type-filter" class="sr-only">{{ __('Filter by type') }}</label>
							<select
								id="modal-type-filter"
								wire:model.live="typeFilter"
								style="
									width: 100%;
									padding: 8px 12px;
									border: 1px solid #d1d5db;
									border-radius: 6px;
									font-size: 14px;
									background-color: white;
									transition: border-color 0.2s;
								"
								onfocus="this.style.borderColor='#3b82f6'; this.style.outline='none';"
								onblur="this.style.borderColor='#d1d5db';"
							>
								<option value="">{{ __('All Types') }}</option>
								<option value="image">{{ __('Images') }}</option>
								<option value="video">{{ __('Videos') }}</option>
								<option value="audio">{{ __('Audio') }}</option>
								<option value="document">{{ __('Documents') }}</option>
							</select>
						</div>

						{{-- Folder Filter --}}
						<div style="min-width: 150px;">
							<label for="modal-folder-filter" class="sr-only">{{ __('Filter by folder') }}</label>
							<select
								id="modal-folder-filter"
								wire:model.live="folderId"
								style="
									width: 100%;
									padding: 8px 12px;
									border: 1px solid #d1d5db;
									border-radius: 6px;
									font-size: 14px;
									background-color: white;
									transition: border-color 0.2s;
								"
								onfocus="this.style.borderColor='#3b82f6'; this.style.outline='none';"
								onblur="this.style.borderColor='#d1d5db';"
							>
								<option value="">{{ __('All Folders') }}</option>
								@foreach ($this->folders as $folder)
									<option value="{{ $folder->id }}">{{ $folder->name }}</option>
								@endforeach
							</select>
						</div>

						{{-- Clear Filters --}}
						@if ($search || $folderId || $typeFilter)
							<button
								wire:click="resetFilters"
								type="button"
								style="
									padding: 8px 16px;
									background-color: #f3f4f6;
									color: #374151;
									border: 1px solid #d1d5db;
									border-radius: 6px;
									font-size: 14px;
									font-weight: 500;
									cursor: pointer;
									transition: all 0.2s;
								"
								onmouseover="this.style.backgroundColor='#e5e7eb';"
								onmouseout="this.style.backgroundColor='#f3f4f6';"
							>
								{{ __('Clear Filters') }}
							</button>
						@endif
					</div>

					{{-- Selected Count --}}
					@if (count($selectedMedia) > 0)
						<div
							style="
								padding: 12px 16px;
								background-color: #eff6ff;
								border: 1px solid #bfdbfe;
								border-radius: 6px;
								margin-bottom: 16px;
								display: flex;
								align-items: center;
								justify-content: space-between;
							"
						>
							<span style="color: #1e40af; font-size: 14px; font-weight: 500;">
								{{ count($selectedMedia) }} {{ count($selectedMedia) === 1 ? __('item selected') : __('items selected') }}
								@if ($maxSelections > 0)
									<span style="color: #6b7280; font-weight: 400;">
										({{ __('max :count', ['count' => $maxSelections]) }})
									</span>
								@endif
							</span>

							<button
								wire:click="clearSelections"
								type="button"
								style="
									padding: 4px 12px;
									background-color: white;
									color: #3b82f6;
									border: 1px solid #3b82f6;
									border-radius: 4px;
									font-size: 13px;
									font-weight: 500;
									cursor: pointer;
									transition: all 0.2s;
								"
								onmouseover="this.style.backgroundColor='#3b82f6'; this.style.color='white';"
								onmouseout="this.style.backgroundColor='white'; this.style.color='#3b82f6';"
							>
								{{ __('Clear') }}
							</button>
						</div>
					@endif

					{{-- Media Grid --}}
					<div
						class="media-modal-grid"
						style="
							display: grid;
							grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
							gap: 16px;
							margin-bottom: 20px;
						"
					>
						@forelse ($this->media as $mediaItem)
							<div
								wire:key="modal-media-{{ $mediaItem->id }}"
								wire:click="toggleSelect({{ $mediaItem->id }})"
								:class="{ 'selected': {{ json_encode(in_array($mediaItem->id, $selectedMedia, true)) }} }"
								tabindex="0"
								role="button"
								aria-pressed="{{ in_array($mediaItem->id, $selectedMedia, true) ? 'true' : 'false' }}"
								style="
									position: relative;
									cursor: pointer;
									border: 2px solid {{ in_array($mediaItem->id, $selectedMedia, true) ? '#3b82f6' : '#e5e7eb' }};
									border-radius: 8px;
									overflow: hidden;
									transition: all 0.2s;
									background-color: {{ in_array($mediaItem->id, $selectedMedia, true) ? '#eff6ff' : 'white' }};
								"
								onmouseover="if (!this.classList.contains('selected')) { this.style.borderColor='#9ca3af'; this.style.boxShadow='0 4px 6px rgba(0,0,0,0.1)'; }"
								onmouseout="if (!this.classList.contains('selected')) { this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'; }"
								@keydown.enter="$wire.toggleSelect({{ $mediaItem->id }})"
								@keydown.space.prevent="$wire.toggleSelect({{ $mediaItem->id }})"
							>
								{{-- Selection Indicator --}}
								@if (in_array($mediaItem->id, $selectedMedia, true))
									<div
										style="
											position: absolute;
											top: 8px;
											right: 8px;
											width: 24px;
											height: 24px;
											background-color: #3b82f6;
											border-radius: 50%;
											display: flex;
											align-items: center;
											justify-content: center;
											z-index: 10;
										"
									>
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="white" style="width: 16px; height: 16px;">
											<path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
										</svg>
									</div>
								@endif

								{{-- Media Preview --}}
								<div
									style="
										width: 100%;
										aspect-ratio: 1;
										background-color: #f3f4f6;
										display: flex;
										align-items: center;
										justify-content: center;
										overflow: hidden;
									"
								>
									@if ($mediaItem->isImage())
										<img
											src="{{ $mediaItem->imageUrl('thumbnail') }}"
											alt="{{ $mediaItem->alt_text ?? $mediaItem->file_name }}"
											loading="lazy"
											style="
												width: 100%;
												height: 100%;
												object-fit: cover;
											"
										/>
									@else
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#9ca3af" style="width: 48px; height: 48px;">
											@if ($mediaItem->isVideo())
												<path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
												<path stroke-linecap="round" stroke-linejoin="round" d="M15.91 11.672a.375.375 0 010 .656l-5.603 3.113a.375.375 0 01-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112z" />
											@elseif ($mediaItem->isAudio())
												<path stroke-linecap="round" stroke-linejoin="round" d="M9 9l10.5-3m0 6.553v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 11-.99-3.467l2.31-.66a2.25 2.25 0 001.632-2.163zm0 0V2.25L9 5.25v10.303m0 0v3.75a2.25 2.25 0 01-1.632 2.163l-1.32.377a1.803 1.803 0 01-.99-3.467l2.31-.66A2.25 2.25 0 009 15.553z" />
											@else
												<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
											@endif
										</svg>
									@endif
								</div>

								{{-- Media Info --}}
								<div style="padding: 8px;">
									<div
										style="
											font-size: 13px;
											color: #111827;
											font-weight: 500;
											white-space: nowrap;
											overflow: hidden;
											text-overflow: ellipsis;
										"
										title="{{ $mediaItem->title ?? $mediaItem->file_name }}"
									>
										{{ $mediaItem->title ?? $mediaItem->file_name }}
									</div>
									<div
										style="
											font-size: 11px;
											color: #6b7280;
											margin-top: 2px;
										"
									>
										{{ $mediaItem->humanFileSize() }}
									</div>
								</div>
							</div>
						@empty
							<div
								style="
									grid-column: 1 / -1;
									text-align: center;
									padding: 48px 24px;
									color: #6b7280;
								"
							>
								<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 48px; height: 48px; margin: 0 auto 16px;">
									<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
								</svg>
								<p style="font-size: 16px; font-weight: 500; margin: 0 0 8px 0;">{{ __('No media found') }}</p>
								<p style="font-size: 14px; margin: 0;">
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
						<div style="margin-top: 20px;">
							{{ $this->media->links() }}
						</div>
					@endif
				</div>

				{{-- Upload Tab --}}
				<div
					x-show="activeTab === 'upload'"
					id="upload-tab-panel"
					role="tabpanel"
					aria-labelledby="upload-tab"
				>
					<livewire:media::media-upload />
				</div>
			</div>

			{{-- Modal Footer (Library Tab Only) --}}
			<div
				x-show="activeTab === 'library'"
				class="media-modal-footer"
				style="
					display: flex;
					align-items: center;
					justify-content: flex-end;
					gap: 12px;
					padding: 16px 24px;
					border-top: 1px solid #e5e7eb;
					background-color: #f9fafb;
				"
			>
				<button
					wire:click="close"
					type="button"
					style="
						padding: 10px 20px;
						background-color: white;
						color: #374151;
						border: 1px solid #d1d5db;
						border-radius: 6px;
						font-size: 14px;
						font-weight: 500;
						cursor: pointer;
						transition: all 0.2s;
					"
					onmouseover="this.style.backgroundColor='#f3f4f6';"
					onmouseout="this.style.backgroundColor='white';"
				>
					{{ __('Cancel') }}
				</button>

				<button
					wire:click="confirmSelection"
					type="button"
					:disabled="{{ count($selectedMedia) === 0 ? 'true' : 'false' }}"
					style="
						padding: 10px 20px;
						background-color: {{ count($selectedMedia) > 0 ? '#3b82f6' : '#9ca3af' }};
						color: white;
						border: none;
						border-radius: 6px;
						font-size: 14px;
						font-weight: 500;
						cursor: {{ count($selectedMedia) > 0 ? 'pointer' : 'not-allowed' }};
						transition: all 0.2s;
					"
					onmouseover="if ({{ count($selectedMedia) > 0 ? 'true' : 'false' }}) { this.style.backgroundColor='#2563eb'; }"
					onmouseout="if ({{ count($selectedMedia) > 0 ? 'true' : 'false' }}) { this.style.backgroundColor='#3b82f6'; }"
				>
					{{ __('Select') }}
					@if (count($selectedMedia) > 0)
						({{ count($selectedMedia) }})
					@endif
				</button>
			</div>
		</div>
	</div>

	<style>
		[x-cloak] { display: none !important; }
		.sr-only {
			position: absolute;
			width: 1px;
			height: 1px;
			padding: 0;
			margin: -1px;
			overflow: hidden;
			clip: rect(0, 0, 0, 0);
			white-space: nowrap;
			border-width: 0;
		}
	</style>
</div>
