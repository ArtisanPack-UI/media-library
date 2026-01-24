<?php

/**
 * Folder Manager Livewire Component
 *
 * Provides a modal interface for creating, editing, and deleting media folders.
 * Supports hierarchical folder structures with parent-child relationships.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Livewire\Components
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use ArtisanPackUI\MediaLibrary\Models\MediaFolder;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * FolderManager Livewire component for managing media folders.
 *
 * Provides a modal interface for creating, editing, and deleting media folders.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Livewire\Components
 */
class FolderManager extends Component
{
    use Toast;

    /**
     * Whether the modal is open.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public bool $isOpen = false;

    /**
     * Whether the form is in edit mode.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public bool $isEditing = false;

    /**
     * The folder being edited.
     *
     * @since 1.0.0
     *
     * @var MediaFolder|null
     */
    public ?MediaFolder $editingFolder = null;

    /**
     * Form data for creating/editing a folder.
     *
     * @since 1.0.0
     *
     * @var array<string, mixed>
     */
    public array $form = [
        'name'        => '',
        'slug'        => '',
        'description' => '',
        'parent_id'   => null,
    ];

    /**
     * All folders for the list and parent dropdown.
     *
     * @since 1.0.0
     *
     * @var Collection
     */
    public Collection $folders;

    /**
     * Mount the component.
     *
     * @since 1.0.0
     */
    public function mount(): void
    {
        $this->loadFolders();
    }

    /**
     * Load all folders from the database.
     *
     * @since 1.0.0
     */
    public function loadFolders(): void
    {
        $this->folders = MediaFolder::with( [ 'parent', 'children', 'creator' ] )
                                    ->orderBy( 'name' )
                                    ->get();
    }

    /**
     * Get parent folder options for the select component.
     *
     * @since 1.0.0
     *
     * @return array<int, array{key: string|int, label: string}>
     */
    #[Computed]
    public function parentFolderOptions(): array
    {
        $options = [
            [ 'key' => '', 'label' => __( 'No Parent (Root Level)' ) ],
        ];

        foreach ( $this->folders as $folder ) {
            // Skip the folder being edited and its children
            if ( $this->isEditing && $this->editingFolder && $folder->id === $this->editingFolder->id ) {
                continue;
            }

            $options[] = [
                'key'   => $folder->id,
                'label' => $folder->name,
            ];

            // Add children with indentation
            if ( $folder->children->isNotEmpty() ) {
                foreach ( $folder->children as $child ) {
                    // Skip the folder being edited
                    if ( $this->isEditing && $this->editingFolder && $child->id === $this->editingFolder->id ) {
                        continue;
                    }

                    $options[] = [
                        'key'   => $child->id,
                        'label' => '-- ' . $child->name,
                    ];
                }
            }
        }

        return $options;
    }

    /**
     * Open the modal.
     *
     * @since 1.0.0
     */
    #[On( 'open-folder-manager' )]
    public function open(): void
    {
        $this->isOpen = true;
        $this->loadFolders();
    }

    /**
     * Close the modal.
     *
     * @since 1.0.0
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    /**
     * Reset the form to its initial state.
     *
     * @since 1.0.0
     */
    public function resetForm(): void
    {
        $this->isEditing     = false;
        $this->editingFolder = null;
        $this->form          = [
            'name'        => '',
            'slug'        => '',
            'description' => '',
            'parent_id'   => null,
        ];
        $this->resetValidation();
    }

    /**
     * Start editing a folder.
     *
     * @since 1.0.0
     *
     * @param int $folderId The folder ID to edit.
     */
    public function edit( int $folderId ): void
    {
        $this->editingFolder = MediaFolder::findOrFail( $folderId );
        $this->isEditing     = true;

        $this->form = [
            'name'        => $this->editingFolder->name,
            'slug'        => $this->editingFolder->slug,
            'description' => $this->editingFolder->description ?? '',
            'parent_id'   => $this->editingFolder->parent_id,
        ];
    }

    /**
     * Cancel editing and reset the form.
     *
     * @since 1.0.0
     */
    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    /**
     * Auto-generate slug from name.
     *
     * @since 1.0.0
     */
    public function updatedFormName(): void
    {
        if ( ! $this->isEditing ) {
            $this->form['slug'] = Str::slug( $this->form['name'] );
        }
    }

    /**
     * Save the folder (create or update).
     *
     * @since 1.0.0
     */
    public function save(): void
    {
        $rules = [
            'form.name'        => [ 'required', 'string', 'max:255' ],
            'form.slug'        => [ 'required', 'string', 'max:255' ],
            'form.description' => [ 'nullable', 'string' ],
            'form.parent_id'   => [ 'nullable', 'exists:media_folders,id' ],
        ];

        // Add unique validation for slug, excluding current folder if editing
        if ( $this->isEditing && $this->editingFolder ) {
            $rules['form.slug'][] = 'unique:media_folders,slug,' . $this->editingFolder->id;
        } else {
            $rules['form.slug'][] = 'unique:media_folders,slug';
        }

        $validated = $this->validate( $rules );

        try {
            if ( $this->isEditing && $this->editingFolder ) {
                // Update existing folder
                $this->editingFolder->update( [
                                                  'name'        => $this->form['name'],
                                                  'slug'        => $this->form['slug'],
                                                  'description' => $this->form['description'],
                                                  'parent_id'   => $this->form['parent_id'],
                                              ] );

                $this->success( __( 'Folder updated successfully' ) );
            } else {
                // Create new folder
                MediaFolder::create( [
                                         'name'        => $this->form['name'],
                                         'slug'        => $this->form['slug'],
                                         'description' => $this->form['description'],
                                         'parent_id'   => $this->form['parent_id'],
                                         'created_by'  => auth()->id(),
                                     ] );

                $this->success( __( 'Folder created successfully' ) );
            }

            $this->resetForm();
            $this->loadFolders();

            // Notify other components to refresh
            $this->dispatch( 'folders-updated' );
        } catch ( Exception $e ) {
            $this->error( __( 'Failed to save folder: :error', [ 'error' => $e->getMessage() ] ) );
        }
    }

    /**
     * Delete a folder.
     *
     * @since 1.0.0
     *
     * @param int $folderId The folder ID to delete.
     */
    public function delete( int $folderId ): void
    {
        try {
            $folder = MediaFolder::findOrFail( $folderId );

            // Check if folder has children
            if ( $folder->children()->exists() ) {
                $this->error( __( 'Cannot delete folder with subfolders. Please delete or move subfolders first.' ) );

                return;
            }

            // Check if folder has media
            if ( $folder->media()->exists() ) {
                $this->error( __( 'Cannot delete folder with media items. Please delete or move media first.' ) );

                return;
            }

            $folder->delete();

            $this->success( __( 'Folder deleted successfully' ) );
            $this->loadFolders();

            // Notify other components to refresh
            $this->dispatch( 'folders-updated' );
        } catch ( Exception $e ) {
            $this->error( __( 'Failed to delete folder: :error', [ 'error' => $e->getMessage() ] ) );
        }
    }

    /**
     * Renders the component.
     *
     * @since 1.0.0
     *
     * @return View The component view.
     */
    public function render(): View
    {
        return view( 'media::livewire.components.folder-manager' );
    }
}
