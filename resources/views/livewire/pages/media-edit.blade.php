<div class="media-edit-container space-y-6 max-w-4xl mx-auto">
	{{-- Header --}}
	<div class="flex items-center justify-between">
		<x-artisanpack-heading level="1">{{ __('Edit Media') }}</x-artisanpack-heading>
		<x-artisanpack-button :href="route('admin.media')" variant="secondary" size="sm">
			<x-artisanpack-icon name="fas.arrow-left" class="mr-2"/>
			{{ __('Back to Library') }}
		</x-artisanpack-button>
	</div>

	<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
		{{-- Media Preview --}}
		<div class="lg:col-span-1">
			<x-artisanpack-card>
				<x-artisanpack-heading level="2" class="mb-4">{{ __('Preview') }}</x-artisanpack-heading>

				<div
					class="aspect-square bg-zinc-100 dark:bg-zinc-800 rounded-lg overflow-hidden flex items-center justify-center">
					@if($media->isImage())
						<img src="{{ $media->imageUrl('full') }}" alt="{{ $media->alt_text ?? $media->title }}"
							 class="w-full h-full object-contain"/>
					@elseif($media->isVideo())
						<video controls class="w-full h-full">
							<source src="{{ $media->url() }}" type="{{ $media->mime_type }}"/>
							{{ __('Your browser does not support the video tag.') }}
						</video>
					@elseif($media->isAudio())
						<div class="text-center">
							<x-artisanpack-icon name="fas.music" class="w-16 h-16 mx-auto mb-4 text-zinc-400"/>
							<audio controls class="w-full">
								<source src="{{ $media->url() }}" type="{{ $media->mime_type }}"/>
								{{ __('Your browser does not support the audio tag.') }}
							</audio>
						</div>
					@else
						<div class="text-center">
							<x-artisanpack-icon name="fas.file" class="w-16 h-16 mx-auto mb-4 text-zinc-400"/>
							<p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $media->file_name }}</p>
						</div>
					@endif
				</div>

				{{-- File Info --}}
				<div class="mt-4 space-y-2 text-sm">
					<div class="flex justify-between">
						<span class="text-zinc-600 dark:text-zinc-400">{{ __('File Name:') }}</span>
						<span class="font-medium text-zinc-900 dark:text-white">{{ $media->file_name }}</span>
					</div>
					<div class="flex justify-between">
						<span class="text-zinc-600 dark:text-zinc-400">{{ __('File Type:') }}</span>
						<span class="font-medium text-zinc-900 dark:text-white">{{ $media->mime_type }}</span>
					</div>
					<div class="flex justify-between">
						<span class="text-zinc-600 dark:text-zinc-400">{{ __('File Size:') }}</span>
						<span class="font-medium text-zinc-900 dark:text-white">{{ $media->humanFileSize() }}</span>
					</div>
					@if($media->isImage() && $media->width && $media->height)
						<div class="flex justify-between">
							<span class="text-zinc-600 dark:text-zinc-400">{{ __('Dimensions:') }}</span>
							<span
								class="font-medium text-zinc-900 dark:text-white">{{ $media->width }} × {{ $media->height }}</span>
						</div>
					@endif
					<div class="flex justify-between">
						<span class="text-zinc-600 dark:text-zinc-400">{{ __('Uploaded:') }}</span>
						<span
							class="font-medium text-zinc-900 dark:text-white">{{ $media->created_at->format('M j, Y') }}</span>
					</div>
					@if($media->uploadedBy)
						<div class="flex justify-between">
							<span class="text-zinc-600 dark:text-zinc-400">{{ __('Uploaded By:') }}</span>
							<span
								class="font-medium text-zinc-900 dark:text-white">{{ $media->uploadedBy->name() }}</span>
						</div>
					@endif
				</div>

				{{-- File URL --}}
				<div class="mt-4">
					<x-artisanpack-heading level="3" class="mb-4">{{ __('File URL') }}</x-artisanpack-heading>
					<div class="flex gap-2">
						<x-artisanpack-input
							value="{{ $media->url() }}"
							readonly
							class="flex-1"
						/>
						<x-artisanpack-button
							variant="secondary"
							size="sm"
							onclick="navigator.clipboard.writeText('{{ $media->url() }}')"
							:title="__('Copy URL')"
						>
							<x-artisanpack-icon name="fas.copy"/>
						</x-artisanpack-button>
					</div>
				</div>
			</x-artisanpack-card>
		</div>

		{{-- Edit Form --}}
		<div class="lg:col-span-2">
			<x-artisanpack-card>
				<x-artisanpack-heading level="2" class="mb-6">{{ __('Media Details') }}</x-artisanpack-heading>

				<div class="space-y-4">
					{{-- Title --}}
					<x-artisanpack-input
						wire:model="form.title"
						:label="__('Title')"
						:placeholder="__('Enter title...')"
					/>

					{{-- Alt Text --}}
					@if($media->isImage())
						<x-artisanpack-input
							wire:model="form.alt_text"
							:label="__('Alt Text')"
							:placeholder="__('Enter alt text for accessibility...')"
							:help="__('Describe the image for screen readers and search engines')"
						/>
					@endif

					{{-- Caption --}}
					<x-artisanpack-textarea
						wire:model="form.caption"
						:label="__('Caption')"
						:placeholder="__('Enter caption...')"
						rows="2"
					/>

					{{-- Description --}}
					<x-artisanpack-textarea
						wire:model="form.description"
						:label="__('Description')"
						:placeholder="__('Enter description...')"
						rows="4"
					/>

					{{-- Folder --}}
					<x-artisanpack-select
						wire:model="form.folder_id"
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

					{{-- Tags --}}
					<div>
						<x-artisanpack-heading level="3" class="mb-4">{{ __('Tags') }}</x-artisanpack-heading>
						<div class="flex flex-wrap gap-2 mt-2">
							@foreach($this->tags as $tag)
								<label class="inline-flex items-center">
									<input
										type="checkbox"
										wire:model="selectedTags"
										value="{{ $tag->id }}"
										class="rounded border-zinc-300 text-primary focus:ring-primary dark:border-zinc-700 dark:bg-zinc-800"
									/>
									<span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300">{{ $tag->name }}</span>
								</label>
							@endforeach
						</div>
					</div>
				</div>

				{{-- Action Buttons --}}
				<div class="mt-6 flex justify-between items-center">
					<x-artisanpack-button
						wire:click="delete"
						wire:confirm="{{ __('Are you sure you want to delete this media? This action cannot be undone.') }}"
						variant="danger"
						size="md"
					>
						<x-artisanpack-icon name="fas.trash" class="mr-2"/>
						{{ __('Delete Media') }}
					</x-artisanpack-button>

					<x-artisanpack-button
						wire:click="save"
						wire:loading.attr="disabled"
						variant="primary"
						size="md"
					>
						<span wire:loading.remove wire:target="save">
							<x-artisanpack-icon name="fas.save" class="mr-2"/>
							{{ __('Save Changes') }}
						</span>
						<span wire:loading wire:target="save">
							<x-artisanpack-loading class="w-5 h-5 mr-2"/>
							{{ __('Saving...') }}
						</span>
					</x-artisanpack-button>
				</div>
			</x-artisanpack-card>
		</div>
	</div>
</div>
