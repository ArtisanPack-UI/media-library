<div class="media-upload-container space-y-6 max-w-4xl mx-auto">
	{{-- Header --}}
	<div class="flex items-center justify-between">
		<x-artisanpack-heading level="1">{{ __('Upload Media') }}</x-artisanpack-heading>
		<x-artisanpack-button :href="route('admin.media')" variant="secondary" size="sm">
			<x-artisanpack-icon name="fas.arrow-left" class="mr-2" />
			{{ __('Back to Library') }}
		</x-artisanpack-button>
	</div>

	{{-- Upload Form --}}
	<x-artisanpack-card>
		{{-- Drag and Drop Zone --}}
		<div
			class="border-2 border-dashed rounded-lg p-12 text-center transition-all"
			:class="{
				'border-primary bg-primary/5': isDragging,
				'border-success bg-success/5': {{ count($files) > 0 ? 'true' : 'false' }},
				'border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 hover:border-primary hover:bg-primary/5': {{ count($files) > 0 ? 'false' : 'true' }} && !isDragging
			}"
			x-data="{
				isDragging: false,
				handleDrop(e) {
					this.isDragging = false;
					const files = Array.from(e.dataTransfer.files);
					@this.upload('files', files);
				}
			}"
			x-on:dragover.prevent="isDragging = true"
			x-on:dragleave.prevent="isDragging = false"
			x-on:drop.prevent="handleDrop($event)"
		>
			<x-artisanpack-icon name="fas.cloud-upload-alt" class="w-16 h-16 mx-auto mb-4 text-zinc-400" />
			<x-artisanpack-heading level="3" class="mb-2">{{ __('Drag and drop files here') }}</x-artisanpack-heading>
			<p class="text-zinc-600 dark:text-zinc-400 mb-4">{{ __('or click to browse') }}</p>

			<x-artisanpack-file
				wire:model="files"
				multiple
				class="hidden"
				id="file-input"
			/>

			<label for="file-input">
				<x-artisanpack-button as="span" variant="primary">
					{{ __('Choose Files') }}
				</x-artisanpack-button>
			</label>
		</div>

		{{-- File Loading Indicator --}}
		<div wire:loading wire:target="files" class="flex items-center justify-center py-8">
			<x-artisanpack-loading class="w-8 h-8" />
			<span class="ml-2">{{ __('Loading files...') }}</span>
		</div>

		{{-- File Preview List --}}
		@if(count($files) > 0)
			<div class="mt-6 space-y-4">
				<div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-zinc-700">
					<x-artisanpack-heading level="3">{{ __('Selected Files') }} ({{ count($files) }})</x-artisanpack-heading>
					<x-artisanpack-button wire:click="clearFiles" variant="ghost" size="sm">
						{{ __('Clear All') }}
					</x-artisanpack-button>
				</div>

				<div class="space-y-2">
					@foreach($files as $index => $file)
						<div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700" wire:key="file-{{ $index }}">
							<div class="w-16 h-16 flex items-center justify-center bg-white dark:bg-zinc-800 rounded-lg overflow-hidden flex-shrink-0">
								@if(str_starts_with($file->getMimeType(), 'image/'))
									<img src="{{ $file->temporaryUrl() }}" alt="{{ $file->getClientOriginalName() }}" class="w-full h-full object-cover" />
								@elseif(str_starts_with($file->getMimeType(), 'video/'))
									<x-artisanpack-icon name="fas.video" class="w-8 h-8 text-zinc-400" />
								@elseif(str_starts_with($file->getMimeType(), 'audio/'))
									<x-artisanpack-icon name="fas.music" class="w-8 h-8 text-zinc-400" />
								@else
									<x-artisanpack-icon name="fas.file" class="w-8 h-8 text-zinc-400" />
								@endif
							</div>

							<div class="flex-1 min-w-0">
								<p class="font-medium text-zinc-900 dark:text-white truncate">{{ $file->getClientOriginalName() }}</p>
								<p class="text-sm text-zinc-600 dark:text-zinc-400">{{ number_format($file->getSize() / 1024, 2) }} KB</p>
							</div>

							<x-artisanpack-button
								wire:click="removeFile({{ $index }})"
								variant="danger"
								size="sm"
								:title="__('Remove')"
							>
								<x-artisanpack-icon name="fas.times" />
							</x-artisanpack-button>
						</div>
					@endforeach
				</div>
			</div>
		@endif

		{{-- Upload Options --}}
		@if(count($files) > 0)
			<div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700 space-y-4">
				<x-artisanpack-heading level="3">{{ __('Upload Options') }}</x-artisanpack-heading>

				<x-artisanpack-select
					wire:model="folderId"
					:label="__('Folder')"
				>
					<option value="">{{ __('No Folder') }}</option>
					@foreach($this->folders as $folder)
						<option value="{{ $folder->id }}">{{ $folder->name }}</option>
						@if($folder->children->isNotEmpty())
							@foreach($folder->children as $child)
								<option value="{{ $child->id }}">-- {{ $child->name }}</option>
							@endforeach
						@endif
					@endforeach
				</x-artisanpack-select>

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
					wire:click="upload"
					wire:loading.attr="disabled"
					variant="primary"
					size="lg"
				>
					<span wire:loading.remove wire:target="upload">
						<x-artisanpack-icon name="fas.upload" class="mr-2" />
						{{ __('Upload :count File(s)', ['count' => count($files)]) }}
					</span>
					<span wire:loading wire:target="upload">
						<x-artisanpack-loading class="w-5 h-5 mr-2" />
						{{ __('Uploading...') }}
					</span>
				</x-artisanpack-button>
			</div>
		@endif

		{{-- Upload Progress --}}
		@if($isUploading || $uploadProgress > 0)
			<div class="mt-6">
				<div class="flex items-center justify-between mb-2">
					<span class="text-sm font-medium">{{ __('Uploading :current of :total files', ['current' => $uploadedCount, 'total' => $totalFiles]) }}</span>
					<span class="text-sm font-medium">{{ $uploadProgress }}%</span>
				</div>
				<x-artisanpack-progress :value="$uploadProgress" />
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
					<div class="flex items-center gap-4 p-4 bg-success/5 rounded-lg border border-success" wire:key="uploaded-{{ $media->id }}">
						<div class="w-16 h-16 flex items-center justify-center bg-white dark:bg-zinc-800 rounded-lg overflow-hidden flex-shrink-0">
							@if($media->isImage())
								<img src="{{ $media->imageUrl('thumbnail') }}" alt="{{ $media->alt_text ?? $media->title }}" class="w-full h-full object-cover" />
							@elseif($media->isVideo())
								<x-artisanpack-icon name="fas.video" class="w-8 h-8 text-success" />
							@elseif($media->isAudio())
								<x-artisanpack-icon name="fas.music" class="w-8 h-8 text-success" />
							@else
								<x-artisanpack-icon name="fas.file" class="w-8 h-8 text-success" />
							@endif
						</div>

						<div class="flex-1 min-w-0">
							<p class="font-medium text-zinc-900 dark:text-white truncate">{{ $media->title ?? $media->file_name }}</p>
							<p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $media->humanFileSize() }}</p>
						</div>

						<x-artisanpack-button :href="route('admin.media.edit', $media->id)" variant="secondary" size="sm">
							{{ __('Edit') }}
						</x-artisanpack-button>
					</div>
				@endforeach
			</div>
		</x-artisanpack-card>
	@endif

	{{-- Upload Errors --}}
	@if(count($uploadErrors) > 0)
		<x-artisanpack-alert variant="error">
			<x-artisanpack-heading level="3" class="mb-2">{{ __('Upload Errors') }}</x-artisanpack-heading>
			<ul class="list-disc list-inside space-y-1">
				@foreach($uploadErrors as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</x-artisanpack-alert>
	@endif
</div>
