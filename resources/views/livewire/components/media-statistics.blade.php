<div class="space-y-6">
	{{-- Overview Stats --}}
	<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
		{{-- Total Media --}}
		<x-artisanpack-stat
			:title="__('Total Media')"
			:value="$this->totalMedia"
			icon="fas.images"
			color="text-primary"
			:animate="true"
		/>

		{{-- Total Storage --}}
		<x-artisanpack-stat
			:title="__('Storage Used')"
			:value="$this->totalStorageFormatted"
			icon="fas.database"
			color="text-secondary"
		/>

		{{-- Recent Uploads --}}
		<x-artisanpack-stat
			:title="__('Uploads (Last :days Days)', ['days' => $recentDays])"
			:value="$this->recentUploadsCount"
			icon="fas.cloud-arrow-up"
			color="text-success"
			:animate="true"
			:sparkline-data="$this->dailyUploadCounts"
			sparkline-type="bar"
			sparkline-color="success"
		/>

		{{-- Average File Size --}}
		<x-artisanpack-stat
			:title="__('Avg. File Size')"
			:value="$this->averageFileSize"
			icon="fas.file"
			color="text-info"
		/>
	</div>

	{{-- Media Type Breakdown --}}
	<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
		{{-- Count by Type --}}
		<x-artisanpack-card>
			<x-slot:header>
				<div class="flex items-center gap-2">
					<x-artisanpack-icon name="fas.chart-pie" class="w-5 h-5 text-primary" />
					<h3 class="text-lg font-semibold">{{ __('Media by Type') }}</h3>
				</div>
			</x-slot:header>

			<div class="space-y-4">
				@php
					$types = [
						'images' => ['icon' => 'fas.image', 'color' => 'primary', 'textClass' => 'text-primary', 'label' => __('Images')],
						'videos' => ['icon' => 'fas.video', 'color' => 'secondary', 'textClass' => 'text-secondary', 'label' => __('Videos')],
						'audio' => ['icon' => 'fas.music', 'color' => 'accent', 'textClass' => 'text-accent', 'label' => __('Audio')],
						'documents' => ['icon' => 'fas.file-lines', 'color' => 'info', 'textClass' => 'text-info', 'label' => __('Documents')],
					];
					$mediaByType = $this->mediaByType;
					$totalMedia = $this->totalMedia;
				@endphp

				@foreach($types as $type => $config)
					@php
						$count = $mediaByType[$type] ?? 0;
						$percentage = $totalMedia > 0 ? round(($count / $totalMedia) * 100, 1) : 0;
					@endphp
					<div class="flex items-center gap-4">
						<div class="flex items-center gap-2 w-32">
							<x-artisanpack-icon :name="$config['icon']" class="w-5 h-5 {{ $config['textClass'] }}" />
							<span class="text-sm font-medium">{{ $config['label'] }}</span>
						</div>
						<div class="flex-1">
							<x-artisanpack-progress
								:value="$percentage"
								:color="$config['color']"
								size="sm"
							/>
						</div>
						<div class="w-20 text-right">
							<span class="text-sm font-semibold">{{ number_format($count) }}</span>
							<span class="text-xs text-zinc-500">({{ $percentage }}%)</span>
						</div>
					</div>
				@endforeach
			</div>
		</x-artisanpack-card>

		{{-- Storage by Type --}}
		<x-artisanpack-card>
			<x-slot:header>
				<div class="flex items-center gap-2">
					<x-artisanpack-icon name="fas.hard-drive" class="w-5 h-5 text-secondary" />
					<h3 class="text-lg font-semibold">{{ __('Storage by Type') }}</h3>
				</div>
			</x-slot:header>

			<div class="space-y-4">
				@php
					$storageByType = $this->storageByType;
					$totalBytes = $this->totalStorageBytes;
				@endphp

				@foreach($types as $type => $config)
					@php
						$storage = $storageByType[$type] ?? ['bytes' => 0, 'formatted' => '0 B'];
						$percentage = $totalBytes > 0 ? round(($storage['bytes'] / $totalBytes) * 100, 1) : 0;
					@endphp
					<div class="flex items-center gap-4">
						<div class="flex items-center gap-2 w-32">
							<x-artisanpack-icon :name="$config['icon']" class="w-5 h-5 {{ $config['textClass'] }}" />
							<span class="text-sm font-medium">{{ $config['label'] }}</span>
						</div>
						<div class="flex-1">
							<x-artisanpack-progress
								:value="$percentage"
								:color="$config['color']"
								size="sm"
							/>
						</div>
						<div class="w-24 text-right">
							<span class="text-sm font-semibold">{{ $storage['formatted'] }}</span>
						</div>
					</div>
				@endforeach
			</div>
		</x-artisanpack-card>
	</div>

	{{-- Folders and Tags --}}
	<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
		{{-- Top Folders --}}
		<x-artisanpack-card>
			<x-slot:header>
				<div class="flex items-center justify-between">
					<div class="flex items-center gap-2">
						<x-artisanpack-icon name="fas.folder" class="w-5 h-5 text-warning" />
						<h3 class="text-lg font-semibold">{{ __('Top Folders') }}</h3>
					</div>
					<x-artisanpack-badge variant="secondary" size="sm">
						{{ $this->totalFolders }} {{ __('total') }}
					</x-artisanpack-badge>
				</div>
			</x-slot:header>

			@if($this->topFolders->isEmpty())
				<div class="text-center py-8 text-zinc-500">
					<x-artisanpack-icon name="fas.folder-open" class="w-12 h-12 mx-auto mb-2 opacity-50" />
					<p>{{ __('No folders created yet') }}</p>
				</div>
			@else
				<div class="space-y-3">
					@foreach($this->topFolders as $folder)
						<div class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
							<div class="flex items-center gap-3">
								<x-artisanpack-icon name="fas.folder" class="w-5 h-5 text-warning" />
								<span class="font-medium">{{ $folder->name }}</span>
							</div>
							<x-artisanpack-badge variant="primary" size="sm">
								{{ trans_choice(':count file|:count files', $folder->media_count, ['count' => $folder->media_count]) }}
							</x-artisanpack-badge>
						</div>
					@endforeach
				</div>
			@endif
		</x-artisanpack-card>

		{{-- Top Tags --}}
		<x-artisanpack-card>
			<x-slot:header>
				<div class="flex items-center justify-between">
					<div class="flex items-center gap-2">
						<x-artisanpack-icon name="fas.tags" class="w-5 h-5 text-accent" />
						<h3 class="text-lg font-semibold">{{ __('Top Tags') }}</h3>
					</div>
					<x-artisanpack-badge variant="secondary" size="sm">
						{{ $this->totalTags }} {{ __('total') }}
					</x-artisanpack-badge>
				</div>
			</x-slot:header>

			@if($this->topTags->isEmpty())
				<div class="text-center py-8 text-zinc-500">
					<x-artisanpack-icon name="fas.tag" class="w-12 h-12 mx-auto mb-2 opacity-50" />
					<p>{{ __('No tags created yet') }}</p>
				</div>
			@else
				<div class="space-y-3">
					@foreach($this->topTags as $tag)
						<div class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
							<div class="flex items-center gap-3">
								<x-artisanpack-icon name="fas.tag" class="w-5 h-5 text-accent" />
								<span class="font-medium">{{ $tag->name }}</span>
							</div>
							<x-artisanpack-badge variant="accent" size="sm">
								{{ trans_choice(':count file|:count files', $tag->media_count, ['count' => $tag->media_count]) }}
							</x-artisanpack-badge>
						</div>
					@endforeach
				</div>
			@endif
		</x-artisanpack-card>
	</div>

	{{-- Largest File --}}
	@if($this->largestFile)
		<x-artisanpack-card>
			<x-slot:header>
				<div class="flex items-center gap-2">
					<x-artisanpack-icon name="fas.file-zipper" class="w-5 h-5 text-error" />
					<h3 class="text-lg font-semibold">{{ __('Largest File') }}</h3>
				</div>
			</x-slot:header>

			<div class="flex items-center gap-4">
				{{-- Preview --}}
				<div class="w-20 h-20 rounded-lg overflow-hidden bg-base-200 flex items-center justify-center flex-shrink-0">
					@if($this->largestFile->isImage())
						<img
							src="{{ $this->largestFile->imageUrl('thumbnail') }}"
							alt="{{ $this->largestFile->alt_text ?? $this->largestFile->title }}"
							class="w-full h-full object-cover"
						/>
					@elseif($this->largestFile->isVideo())
						<x-artisanpack-icon name="fas.video" class="w-10 h-10 text-zinc-400" />
					@elseif($this->largestFile->isAudio())
						<x-artisanpack-icon name="fas.music" class="w-10 h-10 text-zinc-400" />
					@else
						<x-artisanpack-icon name="fas.file" class="w-10 h-10 text-zinc-400" />
					@endif
				</div>

				{{-- Info --}}
				<div class="flex-1 min-w-0">
					<h4 class="font-semibold truncate">{{ $this->largestFile->title ?? $this->largestFile->file_name }}</h4>
					<p class="text-sm text-zinc-500 truncate">{{ $this->largestFile->file_name }}</p>
					<div class="flex items-center gap-4 mt-2 text-sm">
						<span class="font-medium text-error">{{ $this->largestFile->humanFileSize() }}</span>
						@if($this->largestFile->width && $this->largestFile->height)
							<span class="text-zinc-500">{{ $this->largestFile->width }} x {{ $this->largestFile->height }}</span>
						@endif
						<span class="text-zinc-500">{{ $this->largestFile->mime_type }}</span>
					</div>
				</div>

				{{-- Actions --}}
				@if(Route::has('admin.media.edit'))
					<div class="flex-shrink-0">
						<x-artisanpack-button
							:href="route('admin.media.edit', $this->largestFile->id)"
							variant="outline"
							size="sm"
						>
							{{ __('View') }}
						</x-artisanpack-button>
					</div>
				@endif
			</div>
		</x-artisanpack-card>
	@endif
</div>
