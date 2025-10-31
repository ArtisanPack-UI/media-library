<div class="media-upload-container space-y-6 max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <x-artisanpack-heading level="1">{{ __('Upload Media') }}</x-artisanpack-heading>
        <x-artisanpack-button :href="route('admin.media')" variant="outline" size="sm">
            <x-artisanpack-icon name="fas.arrow-left" class="mr-2"/>
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
				'border-success bg-success/5': {{ $this->totalFilesCount > 0 ? 'true' : 'false' }},
				'border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 hover:border-primary hover:bg-primary/5': {{ $this->totalFilesCount > 0 ? 'false' : 'true' }} && !isDragging
			}"
                x-data="{
				isDragging: false,
				dragCounter: 0,
				openFilePicker() {
					this.$refs.fileInput.click();
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
                        <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700"
                             wire:key="file-{{ $index }}">
                            <div class="w-16 h-16 flex items-center justify-center bg-white dark:bg-zinc-800 rounded-lg overflow-hidden flex-shrink-0">
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
                                    :title="__('Remove')"
                            >
                                <x-artisanpack-icon name="fas.times"/>
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
                        <div class="flex items-center gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700"
                             wire:key="dropped-file-{{ $index }}">
                            <div class="w-16 h-16 flex items-center justify-center bg-white dark:bg-zinc-800 rounded-lg overflow-hidden flex-shrink-0">
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
                                    :title="__('Remove')"
                            >
                                <x-artisanpack-icon name="fas.times"/>
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
            <div class="mt-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">{{ __('Uploading :current of :total files', ['current' => $uploadedCount, 'total' => $totalFiles]) }}</span>
                    <span class="text-sm font-medium">{{ $uploadProgress }}%</span>
                </div>
                <x-artisanpack-progress :value="$uploadProgress"/>
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
                        <div class="w-16 h-16 flex items-center justify-center bg-white dark:bg-zinc-800 rounded-lg overflow-hidden flex-shrink-0">
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

                        <x-artisanpack-button :href="route('admin.media.edit', $media->id)" variant="outline" size="sm">
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
