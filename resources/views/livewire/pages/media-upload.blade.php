<div class="media-upload-container">
	{{-- Header --}}
	<div class="media-upload-header">
		<h1 class="page-title">{{ __('Upload Media') }}</h1>
		<a href="{{ route('admin.media') }}" class="btn btn-secondary">
			<x-icon-fas-arrow-left class="mr-2" />
			{{ __('Back to Library') }}
		</a>
	</div>

	{{-- Upload Form --}}
	<div class="upload-form">
		{{-- Drag and Drop Zone --}}
		<div
			class="dropzone {{ count($files) > 0 ? 'has-files' : '' }}"
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
			:class="{ 'dragging': isDragging }"
		>
			<div class="dropzone-content">
				<x-icon-fas-cloud-upload class="upload-icon" />
				<h3>{{ __('Drag and drop files here') }}</h3>
				<p>{{ __('or click to browse') }}</p>
				<input
					type="file"
					wire:model="files"
					multiple
					class="file-input"
					id="file-input"
				/>
				<label for="file-input" class="btn btn-primary">
					{{ __('Choose Files') }}
				</label>
			</div>
		</div>

		{{-- File Loading Indicator --}}
		<div wire:loading wire:target="files" class="loading-indicator">
			<div class="spinner"></div>
			<p>{{ __('Loading files...') }}</p>
		</div>

		{{-- File Preview List --}}
		@if(count($files) > 0)
			<div class="file-preview-list">
				<div class="preview-header">
					<h3>{{ __('Selected Files') }} ({{ count($files) }})</h3>
					<button type="button" wire:click="clearFiles" class="btn btn-sm btn-text">
						{{ __('Clear All') }}
					</button>
				</div>

				<div class="preview-items">
					@foreach($files as $index => $file)
						<div class="preview-item" wire:key="file-{{ $index }}">
							<div class="preview-thumbnail">
								@if(str_starts_with($file->getMimeType(), 'image/'))
									<img src="{{ $file->temporaryUrl() }}" alt="{{ $file->getClientOriginalName() }}" />
								@elseif(str_starts_with($file->getMimeType(), 'video/'))
									<x-icon-fas-video />
								@elseif(str_starts_with($file->getMimeType(), 'audio/'))
									<x-icon-fas-music />
								@else
									<x-icon-fas-file />
								@endif
							</div>

							<div class="preview-info">
								<p class="file-name">{{ $file->getClientOriginalName() }}</p>
								<p class="file-size">{{ number_format($file->getSize() / 1024, 2) }} KB</p>
							</div>

							<button
								type="button"
								wire:click="removeFile({{ $index }})"
								class="remove-btn"
								title="{{ __('Remove') }}"
							>
								<x-icon-fas-times />
							</button>
						</div>
					@endforeach
				</div>
			</div>
		@endif

		{{-- Upload Options --}}
		@if(count($files) > 0)
			<div class="upload-options">
				<h3>{{ __('Upload Options') }}</h3>

				<div class="form-group">
					<label for="folder">{{ __('Folder') }}</label>
					<select id="folder" wire:model="folderId" class="form-select">
						<option value="">{{ __('No Folder') }}</option>
						@foreach($this->folders as $folder)
							<option value="{{ $folder->id }}">{{ $folder->name }}</option>
							@if($folder->children->isNotEmpty())
								@foreach($folder->children as $child)
									<option value="{{ $child->id }}">-- {{ $child->name }}</option>
								@endforeach
							@endif
						@endforeach
					</select>
				</div>

				<div class="form-group">
					<label for="title">{{ __('Title') }} <span class="optional">{{ __('(Optional)') }}</span></label>
					<input
						type="text"
						id="title"
						wire:model="metadata.title"
						class="form-input"
						placeholder="{{ __('Enter title...') }}"
					/>
				</div>

				<div class="form-group">
					<label for="alt_text">{{ __('Alt Text') }} <span class="optional">{{ __('(Optional)') }}</span></label>
					<input
						type="text"
						id="alt_text"
						wire:model="metadata.alt_text"
						class="form-input"
						placeholder="{{ __('Enter alt text...') }}"
					/>
				</div>

				<div class="form-group">
					<label for="caption">{{ __('Caption') }} <span class="optional">{{ __('(Optional)') }}</span></label>
					<textarea
						id="caption"
						wire:model="metadata.caption"
						class="form-textarea"
						rows="2"
						placeholder="{{ __('Enter caption...') }}"
					></textarea>
				</div>

				<div class="form-group">
					<label for="description">{{ __('Description') }} <span class="optional">{{ __('(Optional)') }}</span></label>
					<textarea
						id="description"
						wire:model="metadata.description"
						class="form-textarea"
						rows="3"
						placeholder="{{ __('Enter description...') }}"
					></textarea>
				</div>
			</div>
		@endif

		{{-- Upload Button --}}
		@if(count($files) > 0)
			<div class="upload-actions">
				<button
					type="button"
					wire:click="upload"
					wire:loading.attr="disabled"
					class="btn btn-primary btn-lg"
				>
					<span wire:loading.remove wire:target="upload">
						<x-icon-fas-upload class="mr-2" />
						{{ __('Upload :count File(s)', ['count' => count($files)]) }}
					</span>
					<span wire:loading wire:target="upload">
						{{ __('Uploading...') }}
					</span>
				</button>
			</div>
		@endif

		{{-- Upload Progress --}}
		@if($isUploading || $uploadProgress > 0)
			<div class="upload-progress">
				<div class="progress-header">
					<span>{{ __('Uploading :current of :total files', ['current' => $uploadedCount, 'total' => $totalFiles]) }}</span>
					<span>{{ $uploadProgress }}%</span>
				</div>
				<div class="progress-bar-container">
					<div class="progress-bar" style="width: {{ $uploadProgress }}%"></div>
				</div>
			</div>
		@endif

		{{-- Uploaded Files List --}}
		@if(count($uploadedMedia) > 0)
			<div class="uploaded-list">
				<div class="uploaded-header">
					<h3>{{ __('Uploaded Successfully') }} ({{ count($uploadedMedia) }})</h3>
					<button type="button" wire:click="clearUploaded" class="btn btn-sm btn-text">
						{{ __('Clear') }}
					</button>
				</div>

				<div class="uploaded-items">
					@foreach($uploadedMedia as $media)
						<div class="uploaded-item" wire:key="uploaded-{{ $media->id }}">
							<div class="uploaded-thumbnail">
								@if($media->isImage())
									<img src="{{ $media->imageUrl('thumbnail') }}" alt="{{ $media->alt_text ?? $media->title }}" />
								@elseif($media->isVideo())
									<x-icon-fas-video />
								@elseif($media->isAudio())
									<x-icon-fas-music />
								@else
									<x-icon-fas-file />
								@endif
							</div>

							<div class="uploaded-info">
								<p class="file-name">{{ $media->title ?? $media->file_name }}</p>
								<p class="file-size">{{ $media->humanFileSize() }}</p>
							</div>

							<div class="uploaded-actions">
								<a href="{{ route('admin.media.edit', $media->id) }}" class="btn btn-sm btn-secondary">
									{{ __('Edit') }}
								</a>
							</div>
						</div>
					@endforeach
				</div>
			</div>
		@endif

		{{-- Upload Errors --}}
		@if(count($uploadErrors) > 0)
			<div class="upload-errors">
				<h3>{{ __('Upload Errors') }}</h3>
				<ul>
					@foreach($uploadErrors as $error)
						<li>{{ $error }}</li>
					@endforeach
				</ul>
			</div>
		@endif
	</div>
</div>

<style>
	.media-upload-container {
		max-width: 1200px;
		margin: 0 auto;
		padding: 2rem;
	}

	.media-upload-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 2rem;
	}

	.page-title {
		font-size: 2rem;
		font-weight: 700;
		margin: 0;
	}

	.upload-form {
		background: white;
		border-radius: 0.5rem;
		padding: 2rem;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.dropzone {
		border: 2px dashed #cbd5e0;
		border-radius: 0.5rem;
		padding: 3rem;
		text-align: center;
		background: #f7fafc;
		transition: all 0.3s;
		cursor: pointer;
		position: relative;
	}

	.dropzone:hover,
	.dropzone.dragging {
		border-color: #4299e1;
		background: #ebf8ff;
	}

	.dropzone.has-files {
		border-color: #48bb78;
		background: #f0fff4;
	}

	.dropzone-content {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 1rem;
	}

	.upload-icon {
		font-size: 4rem;
		color: #a0aec0;
	}

	.dropzone h3 {
		font-size: 1.25rem;
		font-weight: 600;
		margin: 0;
		color: #2d3748;
	}

	.dropzone p {
		color: #718096;
		margin: 0;
	}

	.file-input {
		display: none;
	}

	.loading-indicator {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 1rem;
		padding: 2rem;
	}

	.spinner {
		width: 3rem;
		height: 3rem;
		border: 3px solid #e2e8f0;
		border-top-color: #4299e1;
		border-radius: 50%;
		animation: spin 1s linear infinite;
	}

	@keyframes spin {
		to { transform: rotate(360deg); }
	}

	.file-preview-list {
		margin-top: 2rem;
	}

	.preview-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1rem;
		padding-bottom: 0.5rem;
		border-bottom: 1px solid #e2e8f0;
	}

	.preview-header h3 {
		font-size: 1.125rem;
		font-weight: 600;
		margin: 0;
	}

	.preview-items {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
	}

	.preview-item {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding: 1rem;
		background: #f7fafc;
		border-radius: 0.375rem;
		border: 1px solid #e2e8f0;
	}

	.preview-thumbnail {
		width: 60px;
		height: 60px;
		display: flex;
		align-items: center;
		justify-content: center;
		background: white;
		border-radius: 0.375rem;
		overflow: hidden;
		flex-shrink: 0;
	}

	.preview-thumbnail img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.preview-thumbnail svg {
		font-size: 2rem;
		color: #a0aec0;
	}

	.preview-info {
		flex: 1;
	}

	.file-name {
		font-weight: 500;
		margin: 0 0 0.25rem 0;
		color: #2d3748;
	}

	.file-size {
		font-size: 0.875rem;
		color: #718096;
		margin: 0;
	}

	.remove-btn {
		background: #fc8181;
		color: white;
		border: none;
		border-radius: 0.375rem;
		padding: 0.5rem;
		cursor: pointer;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: background 0.2s;
	}

	.remove-btn:hover {
		background: #f56565;
	}

	.upload-options {
		margin-top: 2rem;
		padding-top: 2rem;
		border-top: 1px solid #e2e8f0;
	}

	.upload-options h3 {
		font-size: 1.125rem;
		font-weight: 600;
		margin: 0 0 1.5rem 0;
	}

	.form-group {
		margin-bottom: 1.5rem;
	}

	.form-group label {
		display: block;
		font-weight: 500;
		margin-bottom: 0.5rem;
		color: #2d3748;
	}

	.optional {
		font-weight: 400;
		color: #718096;
		font-size: 0.875rem;
	}

	.form-input,
	.form-select,
	.form-textarea {
		width: 100%;
		padding: 0.75rem;
		border: 1px solid #cbd5e0;
		border-radius: 0.375rem;
		font-size: 1rem;
	}

	.form-input:focus,
	.form-select:focus,
	.form-textarea:focus {
		outline: none;
		border-color: #4299e1;
		box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
	}

	.upload-actions {
		margin-top: 2rem;
		display: flex;
		justify-content: center;
	}

	.btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		padding: 0.75rem 1.5rem;
		border-radius: 0.375rem;
		font-weight: 500;
		cursor: pointer;
		transition: all 0.2s;
		border: none;
		text-decoration: none;
	}

	.btn-primary {
		background: #4299e1;
		color: white;
	}

	.btn-primary:hover {
		background: #3182ce;
	}

	.btn-primary:disabled {
		background: #a0aec0;
		cursor: not-allowed;
	}

	.btn-secondary {
		background: #e2e8f0;
		color: #2d3748;
	}

	.btn-secondary:hover {
		background: #cbd5e0;
	}

	.btn-lg {
		padding: 1rem 2rem;
		font-size: 1.125rem;
	}

	.btn-sm {
		padding: 0.5rem 1rem;
		font-size: 0.875rem;
	}

	.btn-text {
		background: transparent;
		color: #4299e1;
	}

	.btn-text:hover {
		background: #ebf8ff;
	}

	.upload-progress {
		margin-top: 2rem;
		padding: 1.5rem;
		background: #ebf8ff;
		border-radius: 0.5rem;
	}

	.progress-header {
		display: flex;
		justify-content: space-between;
		margin-bottom: 0.75rem;
		font-weight: 500;
		color: #2d3748;
	}

	.progress-bar-container {
		height: 0.5rem;
		background: #bee3f8;
		border-radius: 0.25rem;
		overflow: hidden;
	}

	.progress-bar {
		height: 100%;
		background: #4299e1;
		transition: width 0.3s;
	}

	.uploaded-list {
		margin-top: 2rem;
		padding-top: 2rem;
		border-top: 1px solid #e2e8f0;
	}

	.uploaded-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 1rem;
	}

	.uploaded-header h3 {
		font-size: 1.125rem;
		font-weight: 600;
		margin: 0;
		color: #38a169;
	}

	.uploaded-items {
		display: flex;
		flex-direction: column;
		gap: 0.5rem;
	}

	.uploaded-item {
		display: flex;
		align-items: center;
		gap: 1rem;
		padding: 1rem;
		background: #f0fff4;
		border-radius: 0.375rem;
		border: 1px solid #9ae6b4;
	}

	.uploaded-thumbnail {
		width: 60px;
		height: 60px;
		display: flex;
		align-items: center;
		justify-content: center;
		background: white;
		border-radius: 0.375rem;
		overflow: hidden;
		flex-shrink: 0;
	}

	.uploaded-thumbnail img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.uploaded-thumbnail svg {
		font-size: 2rem;
		color: #48bb78;
	}

	.uploaded-info {
		flex: 1;
	}

	.uploaded-actions {
		display: flex;
		gap: 0.5rem;
	}

	.upload-errors {
		margin-top: 2rem;
		padding: 1.5rem;
		background: #fff5f5;
		border-radius: 0.5rem;
		border: 1px solid #fc8181;
	}

	.upload-errors h3 {
		font-size: 1.125rem;
		font-weight: 600;
		margin: 0 0 1rem 0;
		color: #c53030;
	}

	.upload-errors ul {
		margin: 0;
		padding-left: 1.5rem;
		color: #742a2a;
	}

	.upload-errors li {
		margin-bottom: 0.5rem;
	}

	.mr-2 {
		margin-right: 0.5rem;
	}
</style>
