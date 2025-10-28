<div class="media-item {{ $selected ? 'selected' : '' }}" wire:key="media-{{ $media->id }}">
	{{-- Selection Checkbox (shown in bulk select mode) --}}
	@if($bulkSelectMode)
		<div class="media-checkbox">
			<input
				type="checkbox"
				wire:model="selected"
				wire:change="toggleSelect"
				id="media-{{ $media->id }}"
				class="form-checkbox"
			/>
		</div>
	@endif

	{{-- Media Preview --}}
	<div class="media-preview">
		@if($media->isImage())
			<img
				src="{{ $media->imageUrl('thumbnail') }}"
				alt="{{ $media->alt_text ?? $media->title }}"
				loading="lazy"
				class="media-thumbnail"
			/>
		@elseif($media->isVideo())
			<div class="media-icon video-icon">
				<x-icon-fas-video />
			</div>
		@elseif($media->isAudio())
			<div class="media-icon audio-icon">
				<x-icon-fas-music />
			</div>
		@else
			<div class="media-icon document-icon">
				<x-icon-fas-file />
			</div>
		@endif

		{{-- Hover Actions --}}
		<div class="media-actions">
			<button
				type="button"
				wire:click="copyUrl"
				title="{{ __('Copy URL') }}"
				class="action-btn"
			>
				<x-icon-fas-link />
			</button>

			<button
				type="button"
				wire:click="download"
				title="{{ __('Download') }}"
				class="action-btn"
			>
				<x-icon-fas-download />
			</button>

			<a
				href="{{ route('admin.media.edit', $media->id) }}"
				title="{{ __('Edit') }}"
				class="action-btn"
			>
				<x-icon-fas-edit />
			</a>

			@can('delete', $media)
				<button
					type="button"
					wire:click="delete"
					wire:confirm="{{ __('Are you sure you want to delete this media?') }}"
					title="{{ __('Delete') }}"
					class="action-btn delete-btn"
				>
					<x-icon-fas-trash />
				</button>
			@endcan
		</div>
	</div>

	{{-- Media Info --}}
	<div class="media-info">
		<h4 class="media-title" title="{{ $media->title ?? $media->file_name }}">
			{{ Str::limit($media->title ?? $media->file_name, 30) }}
		</h4>

		<div class="media-meta">
			<span class="file-size">{{ $media->humanFileSize() }}</span>
			@if($media->width && $media->height)
				<span class="dimensions">{{ $media->width }} × {{ $media->height }}</span>
			@endif
		</div>

		@if($media->folder)
			<div class="media-folder">
				<x-icon-fas-folder class="folder-icon" />
				<span>{{ $media->folder->name }}</span>
			</div>
		@endif
	</div>
</div>

<style>
	.media-item {
		position: relative;
		background: white;
		border: 1px solid #e5e7eb;
		border-radius: 0.5rem;
		overflow: hidden;
		transition: all 0.2s;
		cursor: pointer;
	}

	.media-item:hover {
		box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
		transform: translateY(-2px);
	}

	.media-item.selected {
		border-color: #3b82f6;
		box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3);
	}

	.media-checkbox {
		position: absolute;
		top: 0.5rem;
		left: 0.5rem;
		z-index: 10;
	}

	.media-preview {
		position: relative;
		aspect-ratio: 1;
		background: #f3f4f6;
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
	}

	.media-thumbnail {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.media-icon {
		font-size: 3rem;
		color: #9ca3af;
	}

	.media-actions {
		position: absolute;
		bottom: 0;
		left: 0;
		right: 0;
		background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
		padding: 1rem 0.5rem 0.5rem;
		display: flex;
		gap: 0.5rem;
		justify-content: center;
		opacity: 0;
		transition: opacity 0.2s;
	}

	.media-item:hover .media-actions {
		opacity: 1;
	}

	.action-btn {
		background: rgba(255, 255, 255, 0.9);
		border: none;
		border-radius: 0.375rem;
		padding: 0.5rem;
		cursor: pointer;
		transition: background 0.2s;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.action-btn:hover {
		background: white;
	}

	.action-btn.delete-btn {
		background: rgba(239, 68, 68, 0.9);
		color: white;
	}

	.action-btn.delete-btn:hover {
		background: #dc2626;
	}

	.media-info {
		padding: 0.75rem;
	}

	.media-title {
		font-size: 0.875rem;
		font-weight: 500;
		margin: 0 0 0.5rem 0;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.media-meta {
		display: flex;
		gap: 0.5rem;
		font-size: 0.75rem;
		color: #6b7280;
	}

	.media-folder {
		margin-top: 0.5rem;
		display: flex;
		align-items: center;
		gap: 0.25rem;
		font-size: 0.75rem;
		color: #6b7280;
	}

	.folder-icon {
		width: 1rem;
		height: 1rem;
	}
</style>
