@if($bulkSelectMode)
    <div
            class="relative group bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden transition-all hover:shadow-lg hover:-translate-y-0.5"
            :class="{ 'border-primary ring-2 ring-primary/30': {{ $selected ? 'true' : 'false' }} }"
            wire:key="media-{{ $media->id }}"
    >
        @else
            <a
                    href="{{ route('admin.media.edit', $media->id) }}"
                    wire:navigate
                    class="relative group block bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden transition-all hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    wire:key="media-{{ $media->id }}"
            >
                @endif
                {{-- Selection Checkbox (shown in bulk select mode) --}}
                @if($bulkSelectMode)
                    <div class="absolute top-2 left-2 z-10" @click.stop>
                        <x-artisanpack-checkbox
                                wire:model="selected"
                                wire:change="toggleSelect"
                                id="media-{{ $media->id }}"
                        />
                    </div>
                @endif

                {{-- Media Preview --}}
                <div class="relative aspect-square bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center overflow-hidden">
                    @if($media->isImage())
                        <img
                                src="{{ $media->imageUrl('thumbnail') }}"
                                alt="{{ $media->alt_text ?? $media->title }}"
                                loading="lazy"
                                class="w-full h-full object-cover"
                        />
                    @elseif($media->isVideo())
                        <x-artisanpack-icon name="fas.video" class="w-12 h-12 text-zinc-400"/>
                    @elseif($media->isAudio())
                        <x-artisanpack-icon name="fas.music" class="w-12 h-12 text-zinc-400"/>
                    @else
                        <x-artisanpack-icon name="fas.file" class="w-12 h-12 text-zinc-400"/>
                    @endif

                    {{-- Hover Actions --}}
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4 pt-8 flex gap-2 justify-center opacity-0 hover:opacity-100 transition-opacity group-hover:opacity-100">
                        <x-artisanpack-button
                                wire:click.stop="copyUrl"
                                :title="__('Copy URL')"
                                size="sm"
                                class="bg-white/90 hover:bg-white"
                                variant="outline"
                        >
                            <x-artisanpack-icon name="fas.link"/>
                        </x-artisanpack-button>

                        <x-artisanpack-button
                                wire:click.stop="download"
                                :title="__('Download')"
                                size="sm"
                                class="bg-white/90 hover:bg-white"
                                variant="outline"
                        >
                            <x-artisanpack-icon name="fas.download"/>
                        </x-artisanpack-button>

                        <x-artisanpack-button
                                :href="route('admin.media.edit', $media->id)"
                                :title="__('Edit')"
                                size="sm"
                                class="bg-white/90 hover:bg-white"
                                @click.stop
                        >
                            <x-artisanpack-icon name="fas.edit"/>
                        </x-artisanpack-button>

                        @can('delete', $media)
                            <x-artisanpack-button
                                    wire:click.stop="delete"
                                    wire:confirm="{{ __('Are you sure you want to delete this media?') }}"
                                    :title="__('Delete')"
                                    variant="error"
                                    size="sm"
                                    class="bg-danger/90 hover:bg-danger"
                            >
                                <x-artisanpack-icon name="fas.trash"/>
                            </x-artisanpack-button>
                        @endcan
                    </div>
                </div>

                {{-- Media Info --}}
                <div class="p-3">
                    <h4 class="text-sm font-medium text-zinc-900 dark:text-white mb-2 truncate"
                        title="{{ $media->title ?? $media->file_name }}">
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
            @if($bulkSelectMode)
    </div>
    @else
        </a>
@endif
