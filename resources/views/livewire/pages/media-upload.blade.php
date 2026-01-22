<div class="media-upload-container space-y-6 mx-auto">
	{{-- Screen Reader Upload Progress Announcements --}}
	<div
		aria-live="polite"
		aria-atomic="true"
		class="sr-only"
		role="status"
	>
		@if($isUploading)
			{{ __('Uploading :current of :total files. :progress percent complete.', ['current' => $uploadedCount, 'total' => $totalFiles, 'progress' => $uploadProgress]) }}
		@endif
	</div>

	{{-- Header (only shown in standalone mode, not when embedded in modal) --}}
	@if(!isset($embedded) || !$embedded)
		<div class="flex items-center justify-between">
			<x-artisanpack-heading level="1">{{ __('Upload Media') }}</x-artisanpack-heading>
			@if(Route::has('admin.media'))
				<x-artisanpack-button :href="route('admin.media')" variant="outline" size="sm">
					<x-artisanpack-icon name="fas.arrow-left" class="mr-2"/>
					{{ __('Back to Library') }}
				</x-artisanpack-button>
			@endif
		</div>
	@endif

	{{-- Upload Form --}}
	<x-artisanpack-card>
		{{-- Drag and Drop Zone --}}
		<div
			class="border-2 border-dashed rounded-lg p-12 text-center transition-all focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-zinc-900"
			:class="{
				'border-primary bg-primary/5': isDragging,
				'border-success bg-success/5': {{ $this->totalFilesCount > 0 ? 'true' : 'false' }},
				'border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 hover:border-primary hover:bg-primary/5': {{ $this->totalFilesCount > 0 ? 'false' : 'true' }} && !isDragging
			}"
			x-data="{
				isDragging: false,
				dragCounter: 0,
				openFilePicker() {
					this.$refs.fileInput.click();
				},
				handleKeydown(e) {
					// Handle Enter or Space to open file picker
					if (e.key === 'Enter' || e.key === ' ') {
						e.preventDefault();
						this.openFilePicker();
					}
				},
				handleDragEnter(e) {
					e.preventDefault();
					e.stopPropagation();
					this.dragCounter++;
					if (this.dragCounter === 1) {
						this.isDragging = true;
					}
				},
				handleDragLeave(e) {
					e.preventDefault();
					e.stopPropagation();
					this.dragCounter--;
					if (this.dragCounter === 0) {
						this.isDragging = false;
					}
				},
				handleDrop(e) {
					e.preventDefault();
					e.stopPropagation();
					this.isDragging = false;
					this.dragCounter = 0;

					const files = Array.from(e.dataTransfer.files);
					console.log('Files dropped:', files);

					// Use Livewire's uploadMultiple method - uploads to droppedFiles property
					$wire.uploadMultiple('droppedFiles', files,
						(uploadedFilename) => {
							console.log('Upload successful');
						},
						(error) => {
							console.error('Upload error:', error);
						},
						(event) => {
							console.log('Upload progress:', event.detail.progress);
						}
					);
				}
			}"
			tabindex="0"
			role="button"
			aria-label="{{ __('Drop files here or press Enter to browse') }}"
			x-on:keydown="handleKeydown($event)"
			x-on:dragenter="handleDragEnter($event)"
			x-on:dragover.prevent
			x-on:dragleave="handleDragLeave($event)"
			x-on:drop.prevent="handleDrop($event)"
		>
			<x-artisanpack-icon name="fas.cloud-upload-alt" class="w-16 h-16 mx-auto mb-4 text-zinc-400"/>
			<x-artisanpack-heading level="3" class="mb-2">{{ __('Drag and drop files here') }}</x-artisanpack-heading>
			<p class="text-zinc-600 dark:text-zinc-400 mb-4">{{ __('or click to browse') }}</p>

			<div>
				<input
					type="file"
					wire:model="files"
					multiple
					class="hidden"
					id="file-input"
					x-ref="fileInput"
					@change="console.log('File input changed:', $event.target.files)"
				/>

				<x-artisanpack-button @click="openFilePicker(); console.log('Opening file picker')" variant="primary"
									  type="button">
					{{ __('Choose Files') }}
				</x-artisanpack-button>
			</div>
		</div>

		{{-- File Loading Indicator --}}
		<div wire:loading wire:target="files" class="flex items-center justify-center py-8">
			<x-artisanpack-loading class="w-8 h-8"/>
			<span class="ml-2">{{ __('Loading files...') }}</span>
		</div>

		{{-- File Preview List --}}
		@if($this->totalFilesCount > 0)
			<div class="mt-6 space-y-4">
				<div class="flex items-center justify-between pb-3 border-zinc-200 dark:border-zinc-700">
					<x-artisanpack-heading level="3">{{ __('Selected Files') }} ({{ $this->totalFilesCount }})
					</x-artisanpack-heading>
					<x-artisanpack-button wire:click="clearFiles" variant="ghost" size="sm">
						{{ __('Clear All') }}
					</x-artisanpack-button>
				</div>

				<div class="space-y-2">
					{{-- Display files from Choose Files button --}}
					@foreach($files as $index => $file)
						@php
							$fileName = $file->getClientOriginalName();
							$fileSize = number_format($file->getSize() / 1024, 2);
							$mimeType = $file->getMimeType();
						@endphp
						<div
							class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700"
							wire:key="file-{{ $index }}">
							<div
								class="w-16 h-16 flex items-center justify-center bg-white dark:bg-zinc-800 rounded-lg overflow-hidden flex-shrink-0">
								@if(str_starts_with($mimeType, 'image/'))
									<img src="{{ $file->temporaryUrl() }}" alt="{{ $fileName }}"
										 class="w-full h-full object-cover"/>
								@elseif(str_starts_with($mimeType, 'video/'))
									<x-artisanpack-icon name="fas.video" class="w-8 h-8 text-zinc-400"/>
								@elseif(str_starts_with($mimeType, 'audio/'))
									<x-artisanpack-icon name="fas.music" class="w-8 h-8 text-zinc-400"/>
								@else
									<x-artisanpack-icon name="fas.file" class="w-8 h-8 text-zinc-400"/>
								@endif
							</div>

							<div class="flex-1 min-w-0">
								<p class="font-medium text-zinc-900 dark:text-white truncate">{{ $fileName }}</p>
								<p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $fileSize }} KB</p>
							</div>

							<x-artisanpack-button
								wire:click="removeFile({{ $index }})"
								variant="error"
								size="sm"
								aria-label="{{ __('Remove :filename', ['filename' => $fileName]) }}"
							>
								<x-artisanpack-icon name="fas.times" aria-hidden="true"/>
								<span class="sr-only">{{ __('Remove') }}</span>
							</x-artisanpack-button>
						</div>
					@endforeach

					{{-- Display files from drag-and-drop --}}
					@foreach($droppedFiles as $index => $file)
						@php
							$fileName = $file->getClientOriginalName();
							$fileSize = number_format($file->getSize() / 1024, 2);
							$mimeType = $file->getMimeType();
						@endphp
						<div
							class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700"
							wire:key="dropped-file-{{ $index }}">
							<div
								class="w-16 h-16 flex items-center justify-center bg-white dark:bg-zinc-800 rounded-lg overflow-hidden flex-shrink-0">
								@if(str_starts_with($mimeType, 'image/'))
									<img src="{{ $file->temporaryUrl() }}" alt="{{ $fileName }}"
										 class="w-full h-full object-cover"/>
								@elseif(str_starts_with($mimeType, 'video/'))
									<x-artisanpack-icon name="fas.video" class="w-8 h-8 text-zinc-400"/>
								@elseif(str_starts_with($mimeType, 'audio/'))
									<x-artisanpack-icon name="fas.music" class="w-8 h-8 text-zinc-400"/>
								@else
									<x-artisanpack-icon name="fas.file" class="w-8 h-8 text-zinc-400"/>
								@endif
							</div>

							<div class="flex-1 min-w-0">
								<p class="font-medium text-zinc-900 dark:text-white truncate">{{ $fileName }}</p>
								<p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $fileSize }} KB</p>
							</div>

							<x-artisanpack-button
								wire:click="removeDroppedFile({{ $index }})"
								variant="error"
								size="sm"
								aria-label="{{ __('Remove :filename', ['filename' => $fileName]) }}"
							>
								<x-artisanpack-icon name="fas.times" aria-hidden="true"/>
								<span class="sr-only">{{ __('Remove') }}</span>
							</x-artisanpack-button>
						</div>
					@endforeach
				</div>
			</div>
		@endif

		{{-- Upload Options --}}
		@if($this->totalFilesCount > 0)
			<div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700 space-y-4">
				<x-artisanpack-heading level="3">{{ __('Upload Options') }}</x-artisanpack-heading>

				<x-artisanpack-select
					wire:model="folderId"
					:label="__('Folder')"
					:options="$this->folderOptions"
					option-value="key"
					option-label="label"
				/>

				<x-artisanpack-input
					wire:model="metadata.title"
					:label="__('Title') . ' ' . __('(Optional)')"
					:placeholder="__('Enter title...')"
				/>

				<x-artisanpack-input
					wire:model="metadata.alt_text"
					:label="__('Alt Text') . ' ' . __('(Optional)')"
					:placeholder="__('Enter alt text...')"
				/>

				<x-artisanpack-textarea
					wire:model="metadata.caption"
					:label="__('Caption') . ' ' . __('(Optional)')"
					:placeholder="__('Enter caption...')"
					rows="2"
				/>

				<x-artisanpack-textarea
					wire:model="metadata.description"
					:label="__('Description') . ' ' . __('(Optional)')"
					:placeholder="__('Enter description...')"
					rows="3"
				/>
			</div>

			{{-- Upload Button --}}
			<div class="mt-6 flex justify-center">
				<x-artisanpack-button
					wire:click="processUpload"
					wire:loading.attr="disabled"
					variant="primary"
					size="lg"
				>
					<span wire:loading.remove wire:target="processUpload">
						<x-artisanpack-icon name="fas.upload" class="mr-2"/>
						{{ __('Upload :count File(s)', ['count' => $this->totalFilesCount]) }}
					</span>
					<span wire:loading wire:target="processUpload">
						<x-artisanpack-loading class="w-5 h-5 mr-2"/>
						{{ __('Uploading...') }}
					</span>
				</x-artisanpack-button>
			</div>
		@endif

		{{-- Upload Progress --}}
		@if($isUploading || $uploadProgress > 0)
			<div
				class="mt-6"
				{{-- Polling fallback for Livewire 3 (when streaming is not available) --}}
				@if($this->shouldUsePoll)
					wire:poll.{{ $this->pollingInterval }}ms
				@endif
				x-data="{
					progress: {{ $uploadProgress }},
					fileName: {{ Js::from($currentFileName) }},
					fileProgress: {{ $currentFileProgress }},
					current: {{ $uploadedCount }},
					total: {{ $totalFiles }},
					status: '',
					complete: false,
					isStreaming: {{ $this->isStreamingEnabled() ? 'true' : 'false' }},
					progressObserver: null,
					parseStreamData(content) {
						try {
							const data = JSON.parse(content);
							if (data.progress !== undefined) this.progress = data.progress;
							if (data.fileName !== undefined) this.fileName = data.fileName;
							if (data.fileProgress !== undefined) this.fileProgress = data.fileProgress;
							if (data.current !== undefined) this.current = data.current;
							if (data.total !== undefined) this.total = data.total;
							if (data.status !== undefined) this.status = data.status;
							if (data.complete !== undefined) this.complete = data.complete;
						} catch (e) {
							console.error('Failed to parse stream data:', e);
						}
					},
					updateFromServer() {
						// For polling mode, update Alpine state from Livewire properties
						if (!this.isStreaming) {
							this.progress = {{ $uploadProgress }};
							this.current = {{ $uploadedCount }};
							this.total = {{ $totalFiles }};
							this.fileName = {{ Js::from($currentFileName) }};
							this.fileProgress = {{ $currentFileProgress }};
						}
					},
					initProgressObserver() {
						if (!this.isStreaming) return;

						const target = this.$el.querySelector('[wire\\:stream=upload-progress]');
						if (!target) return;

						const self = this;
						this.progressObserver = new MutationObserver((mutations) => {
							for (const mutation of mutations) {
								// Handle added nodes (for childList mutations)
								if (mutation.addedNodes) {
									for (const node of mutation.addedNodes) {
										const content = node.nodeValue || node.textContent;
										if (content && content.trim()) {
											self.parseStreamData(content.trim());
										}
									}
								}
								// Handle characterData mutations (direct text changes)
								if (mutation.type === 'characterData' && mutation.target.nodeValue) {
									self.parseStreamData(mutation.target.nodeValue.trim());
								}
							}
						});

						this.progressObserver.observe(target, {
							childList: true,
							subtree: true,
							characterData: true
						});
					},
					destroyProgressObserver() {
						if (this.progressObserver) {
							this.progressObserver.disconnect();
							this.progressObserver = null;
						}
					}
				}"
				@if($this->isStreamingEnabled())
					x-init="initProgressObserver(); typeof $cleanup === 'function' && $cleanup(() => destroyProgressObserver())"
				@else
					x-init="updateFromServer()"
					x-effect="updateFromServer()"
				@endif
			>
				{{-- Stream target for progress updates (hidden, receives JSON data) --}}
				@if($this->isStreamingEnabled())
					<div wire:stream="upload-progress" class="hidden"></div>
				@endif

				{{-- Progress mode indicator (for development/debugging) --}}
				@if(config('app.debug'))
					<div class="text-xs text-zinc-400 mb-2">
						{{ $this->isStreamingEnabled() ? __('Using streaming (Livewire 4)') : __('Using polling fallback (Livewire 3)') }}
					</div>
				@endif

				<div class="flex items-center justify-between mb-2">
					@if($this->isStreamingEnabled())
						<span class="text-sm font-medium" x-text="status || '{{ __('Uploading :current of :total files', ['current' => $uploadedCount, 'total' => $totalFiles]) }}'">
							{{ __('Uploading :current of :total files', ['current' => $uploadedCount, 'total' => $totalFiles]) }}
						</span>
						<span class="text-sm font-medium" x-text="progress + '%'">{{ $uploadProgress }}%</span>
					@else
						{{-- For polling mode, use server-side values directly --}}
						<span class="text-sm font-medium">
							{{ __('Uploading :current of :total files', ['current' => $uploadedCount, 'total' => $totalFiles]) }}
						</span>
						<span class="text-sm font-medium">{{ $uploadProgress }}%</span>
					@endif
				</div>

				{{-- Overall progress bar --}}
				@if($this->isStreamingEnabled())
					<x-artisanpack-progress :value="$uploadProgress" x-bind:value="progress" />
				@else
					{{-- For polling mode, progress updates via Livewire re-render --}}
					<x-artisanpack-progress :value="$uploadProgress" />
				@endif

				{{-- Current file progress (only shown during streaming) --}}
				@if($this->isStreamingEnabled())
					<div x-show="fileName && !complete" x-cloak class="mt-3">
						<div class="flex items-center justify-between mb-1">
							<span class="text-xs text-zinc-600 dark:text-zinc-400 truncate max-w-xs" x-text="fileName"></span>
							<span class="text-xs text-zinc-600 dark:text-zinc-400" x-text="fileProgress + '%'"></span>
						</div>
						<div
							class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5"
							role="progressbar"
							aria-valuemin="0"
							aria-valuemax="100"
							x-bind:aria-valuenow="fileProgress"
							x-bind:aria-label="'{{ __('File upload progress') }}: ' + fileName"
						>
							<div
								class="bg-primary h-1.5 rounded-full transition-all duration-300"
								x-bind:style="'width: ' + fileProgress + '%'"
							></div>
						</div>
					</div>
				@else
					{{-- Polling mode: Show current file info if available --}}
					@if($currentFileName)
						<div class="mt-3">
							<div class="flex items-center justify-between mb-1">
								<span class="text-xs text-zinc-600 dark:text-zinc-400 truncate max-w-xs">{{ $currentFileName }}</span>
								<span class="text-xs text-zinc-600 dark:text-zinc-400">{{ $currentFileProgress }}%</span>
							</div>
							<div
								class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5"
								role="progressbar"
								aria-valuemin="0"
								aria-valuemax="100"
								aria-valuenow="{{ $currentFileProgress }}"
								aria-label="{{ __('File upload progress') }}: {{ $currentFileName }}"
							>
								<div
									class="bg-primary h-1.5 rounded-full transition-all duration-300"
									style="width: {{ $currentFileProgress }}%"
								></div>
							</div>
						</div>
					@endif
				@endif
			</div>
		@endif

		{{-- Stream target for errors (only when streaming is enabled) --}}
		@if($this->isStreamingEnabled())
			<div
				x-data="{
					errors: [],
					errorObserver: null,
					addError(content) {
						try {
							const data = JSON.parse(content);
							if (data.error && data.message) {
								this.errors.push(data.message);
							}
						} catch (e) {
							console.error('Failed to parse error stream:', e);
						}
					},
					initErrorObserver() {
						const self = this;
						const target = this.$refs.errorStream;
						if (!target) return;

						this.errorObserver = new MutationObserver((mutations) => {
							for (const mutation of mutations) {
								// Handle added nodes (for childList mutations)
								if (mutation.addedNodes) {
									for (const node of mutation.addedNodes) {
										const content = node.nodeValue || node.textContent;
										if (content && content.trim()) {
											self.addError(content.trim());
										}
									}
								}
								// Handle characterData mutations (direct text changes)
								if (mutation.type === 'characterData' && mutation.target.nodeValue) {
									self.addError(mutation.target.nodeValue.trim());
								}
							}
						});

						this.errorObserver.observe(target, {
							childList: true,
							subtree: true,
							characterData: true
						});
					},
					destroyErrorObserver() {
						if (this.errorObserver) {
							this.errorObserver.disconnect();
							this.errorObserver = null;
						}
					}
				}"
				x-init="initErrorObserver(); typeof $cleanup === 'function' && $cleanup(() => destroyErrorObserver())"
			>
				{{-- Hidden stream target --}}
				<div wire:stream="upload-errors" x-ref="errorStream" class="hidden"></div>

				{{-- Render streamed errors --}}
				<template x-if="errors.length > 0">
					<div class="mt-4 space-y-2">
						<template x-for="(error, index) in errors" :key="index">
							<x-artisanpack-alert variant="error" x-text="error"></x-artisanpack-alert>
						</template>
					</div>
				</template>
			</div>
		@endif
	</x-artisanpack-card>

	{{-- Uploaded Files List --}}
	@if(count($uploadedMedia) > 0)
		<x-artisanpack-card>
			<div class="flex items-center justify-between mb-4">
				<x-artisanpack-heading level="3" class="text-success">
					{{ __('Uploaded Successfully') }} ({{ count($uploadedMedia) }})
				</x-artisanpack-heading>
				<x-artisanpack-button wire:click="clearUploaded" variant="ghost" size="sm">
					{{ __('Clear') }}
				</x-artisanpack-button>
			</div>

			<div class="space-y-2">
				@foreach($uploadedMedia as $media)
					<div class="flex items-center gap-4 p-4 bg-success/5 rounded-lg border border-success"
						 wire:key="uploaded-{{ $media->id }}">
						<div
							class="w-16 h-16 flex items-center justify-center bg-white dark:bg-zinc-800 rounded-lg overflow-hidden flex-shrink-0">
							@if($media->isImage())
								<img src="{{ $media->imageUrl('thumbnail') }}"
									 alt="{{ $media->alt_text ?? $media->title }}" class="w-full h-full object-cover"/>
							@elseif($media->isVideo())
								<x-artisanpack-icon name="fas.video" class="w-8 h-8 text-success"/>
							@elseif($media->isAudio())
								<x-artisanpack-icon name="fas.music" class="w-8 h-8 text-success"/>
							@else
								<x-artisanpack-icon name="fas.file" class="w-8 h-8 text-success"/>
							@endif
						</div>

						<div class="flex-1 min-w-0">
							<p class="font-medium text-zinc-900 dark:text-white truncate">{{ $media->title ?? $media->file_name }}</p>
							<p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $media->humanFileSize() }}</p>
						</div>

						@if(Route::has('admin.media.edit'))
							<x-artisanpack-button :href="route('admin.media.edit', $media->id)" variant="outline" size="sm">
								{{ __('Edit') }}
							</x-artisanpack-button>
						@endif
					</div>
				@endforeach
			</div>
		</x-artisanpack-card>
	@endif

	{{-- Upload Errors --}}
	@if(count($uploadErrors) > 0)
		<x-artisanpack-alert variant="error" role="alert">
			<x-artisanpack-heading level="3" class="mb-2">{{ __('Upload Errors') }}</x-artisanpack-heading>
			<ul class="list-disc list-inside space-y-1" aria-label="{{ __('List of upload errors') }}">
				@foreach($uploadErrors as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</x-artisanpack-alert>
	@endif
</div>
