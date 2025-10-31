<div>
    {{-- Modal Overlay --}}
    <div
            x-data="{ open: @entangle('isOpen') }"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
    >
        {{-- Background Overlay --}}
        <div
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black/50 transition-opacity"
                @click="$wire.close()"
        ></div>

        {{-- Modal Panel --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                    x-show="open"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative w-full max-w-4xl transform overflow-hidden rounded-lg bg-white dark:bg-zinc-800 shadow-xl transition-all"
            >
                <x-artisanpack-card>
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between mb-6">
                        <x-artisanpack-heading level="2">
                            {{ __('Manage Folders') }}
                        </x-artisanpack-heading>
                        <button
                                type="button"
                                wire:click="close"
                                class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                        >
                            <x-artisanpack-icon name="fas.times" class="w-5 h-5"/>
                        </button>
                    </div>

                    {{-- Form Section --}}
                    <div class="mb-6">
                        <x-artisanpack-card variant="secondary">
                            <x-artisanpack-heading level="3" class="mb-4">
                                @if($isEditing)
                                    {{ __('Edit Folder') }}
                                @else
                                    {{ __('Create New Folder') }}
                                @endif
                            </x-artisanpack-heading>

                            <form wire:submit.prevent="save" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <x-artisanpack-input
                                            wire:model.live="form.name"
                                            :label="__('Folder Name')"
                                            :placeholder="__('Enter folder name')"
                                            required
                                    />

                                    <x-artisanpack-input
                                            wire:model="form.slug"
                                            :label="__('Slug')"
                                            :placeholder="__('Auto-generated from name')"
                                            :help="__('Used in URLs (automatically generated from name)')"
                                            required
                                    />
                                </div>

                                <x-artisanpack-select
                                        wire:model="form.parent_id"
                                        :label="__('Parent Folder')"
                                >
                                    <option value="">{{ __('No Parent (Root Level)') }}</option>
                                    @foreach($folders as $folder)
                                        @if(!$isEditing || $folder->id !== $editingFolder?->id)
                                            <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                                            @if($folder->children->isNotEmpty())
                                                @foreach($folder->children as $child)
                                                    @if(!$isEditing || $child->id !== $editingFolder?->id)
                                                        <option value="{{ $child->id }}">-- {{ $child->name }}</option>
                                                    @endif
                                                @endforeach
                                            @endif
                                        @endif
                                    @endforeach
                                </x-artisanpack-select>

                                <x-artisanpack-textarea
                                        wire:model="form.description"
                                        :label="__('Description')"
                                        :placeholder="__('Enter folder description (optional)')"
                                        rows="3"
                                />

                                <div class="flex items-center justify-end gap-2">
                                    @if($isEditing)
                                        <x-artisanpack-button
                                                type="button"
                                                wire:click="cancelEdit"
                                                variant="secondary"
                                                size="sm"
                                        >
                                            {{ __('Cancel') }}
                                        </x-artisanpack-button>
                                    @endif

                                    <x-artisanpack-button
                                            type="submit"
                                            variant="primary"
                                            size="sm"
                                    >
                                        @if($isEditing)
                                            {{ __('Update Folder') }}
                                        @else
                                            {{ __('Create Folder') }}
                                        @endif
                                    </x-artisanpack-button>
                                </div>
                            </form>
                        </x-artisanpack-card>
                    </div>

                    {{-- Folders List --}}
                    <div>
                        <x-artisanpack-heading level="3" class="mb-4">
                            {{ __('Existing Folders') }}
                        </x-artisanpack-heading>

                        @if($folders->isEmpty())
                            <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
                                <x-artisanpack-icon name="fas.folder-open" class="w-12 h-12 mx-auto mb-2 opacity-50"/>
                                <p>{{ __('No folders created yet') }}</p>
                            </div>
                        @else
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                @foreach($folders as $folder)
                                    <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <x-artisanpack-icon name="fas.folder"
                                                                    class="w-4 h-4 text-zinc-500 dark:text-zinc-400"/>
                                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
													{{ $folder->name }}
												</span>
                                                @if($folder->parent)
                                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
														({{ __('in') }} {{ $folder->parent->name }})
													</span>
                                                @endif
                                            </div>
                                            @if($folder->description)
                                                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1 ml-6">
                                                    {{ $folder->description }}
                                                </p>
                                            @endif
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 ml-6">
                                                {{ __(':count media items', ['count' => $folder->media()->count()]) }}
                                                @if($folder->children->isNotEmpty())
                                                    · {{ __(':count subfolders', ['count' => $folder->children->count()]) }}
                                                @endif
                                                @if($folder->creator)
                                                    · {{ __('Created by :name', ['name' => method_exists($folder->creator, 'name') ? $folder->creator->name() : ($folder->creator->name ?? $folder->creator->email ?? 'Unknown')]) }}
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2 ml-4">
                                            <x-artisanpack-button
                                                    type="button"
                                                    wire:click="edit({{ $folder->id }})"
                                                    variant="secondary"
                                                    size="sm"
                                            >
                                                <x-artisanpack-icon name="fas.edit" class="w-3 h-3"/>
                                            </x-artisanpack-button>

                                            <x-artisanpack-button
                                                    type="button"
                                                    wire:click="delete({{ $folder->id }})"
                                                    wire:confirm="{{ __('Are you sure you want to delete this folder?') }}"
                                                    variant="error"
                                                    size="sm"
                                            >
                                                <x-artisanpack-icon name="fas.trash" class="w-3 h-3"/>
                                            </x-artisanpack-button>
                                        </div>
                                    </div>

                                    {{-- Show children folders indented --}}
                                    @if($folder->children->isNotEmpty())
                                        @foreach($folder->children as $child)
                                            <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors ml-8">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-2">
                                                        <x-artisanpack-icon name="fas.folder"
                                                                            class="w-4 h-4 text-zinc-500 dark:text-zinc-400"/>
                                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
															{{ $child->name }}
														</span>
                                                    </div>
                                                    @if($child->description)
                                                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1 ml-6">
                                                            {{ $child->description }}
                                                        </p>
                                                    @endif
                                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 ml-6">
                                                        {{ __(':count media items', ['count' => $child->media()->count()]) }}
                                                        @if($child->creator)
                                                            · {{ __('Created by :name', ['name' => method_exists($child->creator, 'name') ? $child->creator->name() : ($child->creator->name ?? $child->creator->email ?? 'Unknown')]) }}
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2 ml-4">
                                                    <x-artisanpack-button
                                                            type="button"
                                                            wire:click="edit({{ $child->id }})"
                                                            variant="secondary"
                                                            size="sm"
                                                    >
                                                        <x-artisanpack-icon name="fas.edit" class="w-3 h-3"/>
                                                    </x-artisanpack-button>

                                                    <x-artisanpack-button
                                                            type="button"
                                                            wire:click="delete({{ $child->id }})"
                                                            wire:confirm="{{ __('Are you sure you want to delete this folder?') }}"
                                                            variant="error"
                                                            size="sm"
                                                    >
                                                        <x-artisanpack-icon name="fas.trash" class="w-3 h-3"/>
                                                    </x-artisanpack-button>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex items-center justify-end mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
                        <x-artisanpack-button
                                type="button"
                                wire:click="close"
                                variant="secondary"
                        >
                            {{ __('Close') }}
                        </x-artisanpack-button>
                    </div>
                </x-artisanpack-card>
            </div>
        </div>
    </div>
</div>
