<div
	class="relative group bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden transition-all hover:shadow-lg hover:-translate-y-0.5 {{ $bulkSelectMode && $selected ? 'border-primary ring-2 ring-primary/30' : '' }}"
	x-data="{
		mediaUrl: @js($media->url()),
		showActions: false,
		copyToClipboard() {
			if (navigator.clipboard) {
				navigator.clipboard.writeText(this.mediaUrl).then(() => {
					$wire.success('{{ __('URL copied to clipboard') }}');
				}).catch(err => {
					console.error('Failed to copy:', err);
					$wire.error('{{ __('Failed to copy URL') }}');
				});
			}
		}
	}"
	@focusin="showActions = true"
	@focusout="showActions = false"
>
	{{-- Selection Checkbox (shown in bulk select mode) --}}
	@if($bulkSelectMode)
		<div class="absolute top-2 left-2 z-10" x-on:click.stop>
			<label class="sr-only" for="media-{{ $media->id }}">
				{{ __('Select :name', ['name' => $media->title ?? $media->file_name]) }}
			</label>
			<input
				type="checkbox"
				wire:click="toggleSelect"
				@checked($selected)
				id="media-{{ $media->id }}"
				class="checkbox checkbox-primary"
				aria-describedby="media-name-{{ $media->id }}"
			/>
		</div>
	@endif

	{{-- Clickable wrapper for non-bulk mode --}}
	@if(!$bulkSelectMode && Route::has('admin.media.edit'))
		<a
			href="{{ route('admin.media.edit', $media->id) }}"
			wire:navigate
			class="block focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
		>
	@endif

	{{-- Media Preview --}}
	<div class="relative aspect-square bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center overflow-hidden">
		@if($media->isImage())
			<img
				src="{{ $media->imageUrl('thumbnail') }}"
				alt="{{ $media->alt_text ?? $media->title ?? __('Media file: :name', ['name' => $media->file_name]) }}"
				loading="lazy"
				class="absolute inset-0 w-full h-full object-cover"
			/>
		@elseif($media->isVideo())
			<x-artisanpack-icon name="fas.video" class="w-12 h-12 text-zinc-400"/>
		@elseif($media->isAudio())
			<x-artisanpack-icon name="fas.music" class="w-12 h-12 text-zinc-400"/>
		@else
			<x-artisanpack-icon name="fas.file" class="w-12 h-12 text-zinc-400"/>
		@endif

		{{-- Hover/Focus Actions --}}
		<div
			class="absolute bottom-0 left-0 right-0 glass-frosted bg-zinc-900/60 dark:bg-zinc-950/70 backdrop-blur-md backdrop-saturate-150 border-t border-white/10 p-4 flex gap-2 justify-center transition-opacity"
			:class="showActions ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
			x-on:click.prevent.stop
		>
			<button
				type="button"
				x-on:click.stop="copyToClipboard()"
				aria-label="{{ __('Copy URL for :name', ['name' => $media->title ?? $media->file_name]) }}"
				class="btn btn-sm btn-outline bg-white/90 hover:bg-white"
			>
				<x-artisanpack-icon name="fas.link" aria-hidden="true"/>
				<span class="sr-only">{{ __('Copy URL') }}</span>
			</button>

			<a
				href="{{ route('media.download', $media->id) }}"
				x-on:click.stop
				target="_blank"
				aria-label="{{ __('Download :name', ['name' => $media->title ?? $media->file_name]) }}"
				class="btn btn-sm btn-outline bg-white/90 hover:bg-white"
			>
				<x-artisanpack-icon name="fas.download" aria-hidden="true"/>
				<span class="sr-only">{{ __('Download') }}</span>
			</a>

			@if(Route::has('admin.media.edit'))
				<a
					href="{{ route('admin.media.edit', $media->id) }}"
					aria-label="{{ __('Edit :name', ['name' => $media->title ?? $media->file_name]) }}"
					class="btn btn-sm bg-white/90 hover:bg-white"
					x-on:click.stop
				>
					<x-artisanpack-icon name="fas.edit" aria-hidden="true"/>
					<span class="sr-only">{{ __('Edit') }}</span>
				</a>
			@endif

			@can('delete', $media)
				<button
					type="button"
					wire:click="delete"
					wire:confirm="{{ __('Are you sure you want to delete this media?') }}"
					aria-label="{{ __('Delete :name', ['name' => $media->title ?? $media->file_name]) }}"
					class="btn btn-sm btn-error bg-danger/90 hover:bg-danger"
					x-on:click.stop
				>
					<x-artisanpack-icon name="fas.trash" aria-hidden="true"/>
					<span class="sr-only">{{ __('Delete') }}</span>
				</button>
			@endcan
		</div>
	</div>

	{{-- Media Info --}}
	<div class="p-3">
		<h4
			id="media-name-{{ $media->id }}"
			class="text-sm font-medium text-zinc-900 dark:text-white mb-2 truncate"
			title="{{ $media->title ?? $media->file_name }}"
		>
			{{ Str::limit($media->title ?? $media->file_name, 30) }}
		</h4>

		<div class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400 mb-2">
			<span>{{ $media->humanFileSize() }}</span>
			@if($media->width && $media->height)
				<span>•</span>
				<span>{{ $media->width }} × {{ $media->height }}</span>
			@endif
		</div>

		@if($media->folder)
			<x-artisanpack-badge variant="secondary" size="sm" class="inline-flex items-center gap-1">
				<x-artisanpack-icon name="fas.folder" class="w-3 h-3"/>
				<span>{{ $media->folder->name }}</span>
			</x-artisanpack-badge>
		@endif
	</div>

	@if(!$bulkSelectMode && Route::has('admin.media.edit'))
		</a>
	@endif
</div>
