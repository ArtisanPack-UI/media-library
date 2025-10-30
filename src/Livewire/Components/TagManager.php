<?php

declare(strict_types=1);

namespace ArtisanPackUI\MediaLibrary\Livewire\Components;

use ArtisanPack\LivewireUiComponents\Traits\Toast;
use ArtisanPackUI\MediaLibrary\Models\MediaTag;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * TagManager Livewire component for managing media tags.
 *
 * Provides a modal interface for creating, editing, and deleting media tags.
 *
 * @since 1.0.0
 *
 * @package ArtisanPackUI\MediaLibrary\Livewire\Components
 */
class TagManager extends Component
{
	use Toast;

	/**
	 * Whether the modal is open.
	 *
	 * @since 1.0.0
	 */
	public bool $isOpen = false;

	/**
	 * Whether the form is in edit mode.
	 *
	 * @since 1.0.0
	 */
	public bool $isEditing = false;

	/**
	 * The tag being edited.
	 *
	 * @since 1.0.0
	 */
	public ?MediaTag $editingTag = null;

	/**
	 * Form data for creating/editing a tag.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, mixed>
	 */
	public array $form = [
		'name' => '',
		'slug' => '',
		'description' => '',
	];

	/**
	 * All tags for the list.
	 *
	 * @since 1.0.0
	 */
	public Collection $tags;

	/**
	 * Mount the component.
	 *
	 * @since 1.0.0
	 */
	public function mount(): void
	{
		$this->loadTags();
	}

	/**
	 * Load all tags from the database.
	 *
	 * @since 1.0.0
	 */
	public function loadTags(): void
	{
		$this->tags = MediaTag::orderBy('name')->get();
	}

	/**
	 * Open the modal.
	 *
	 * @since 1.0.0
	 */
	#[On('open-tag-manager')]
	public function open(): void
	{
		$this->isOpen = true;
		$this->loadTags();
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
		$this->isEditing = false;
		$this->editingTag = null;
		$this->form = [
			'name' => '',
			'slug' => '',
			'description' => '',
		];
		$this->resetValidation();
	}

	/**
	 * Start editing a tag.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $tagId  The tag ID to edit.
	 */
	public function edit(int $tagId): void
	{
		$this->editingTag = MediaTag::findOrFail($tagId);
		$this->isEditing = true;

		$this->form = [
			'name' => $this->editingTag->name,
			'slug' => $this->editingTag->slug,
			'description' => $this->editingTag->description ?? '',
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
		if (! $this->isEditing) {
			$this->form['slug'] = Str::slug($this->form['name']);
		}
	}

	/**
	 * Save the tag (create or update).
	 *
	 * @since 1.0.0
	 */
	public function save(): void
	{
		$rules = [
			'form.name' => ['required', 'string', 'max:255'],
			'form.slug' => ['required', 'string', 'max:255'],
			'form.description' => ['nullable', 'string'],
		];

		// Add unique validation for slug, excluding current tag if editing
		if ($this->isEditing && $this->editingTag) {
			$rules['form.slug'][] = 'unique:media_tags,slug,' . $this->editingTag->id;
		} else {
			$rules['form.slug'][] = 'unique:media_tags,slug';
		}

		$validated = $this->validate($rules);

		try {
			if ($this->isEditing && $this->editingTag) {
				// Update existing tag
				$this->editingTag->update([
					'name' => $this->form['name'],
					'slug' => $this->form['slug'],
					'description' => $this->form['description'],
				]);

				$this->success(__('Tag updated successfully'));
			} else {
				// Create new tag
				MediaTag::create([
					'name' => $this->form['name'],
					'slug' => $this->form['slug'],
					'description' => $this->form['description'],
				]);

				$this->success(__('Tag created successfully'));
			}

			$this->resetForm();
			$this->loadTags();

			// Notify other components to refresh
			$this->dispatch('tags-updated');
		} catch (\Exception $e) {
			$this->error(__('Failed to save tag: :error', ['error' => $e->getMessage()]));
		}
	}

	/**
	 * Delete a tag.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $tagId  The tag ID to delete.
	 */
	public function delete(int $tagId): void
	{
		try {
			$tag = MediaTag::findOrFail($tagId);

			// Detach from all media items before deleting
			$tag->media()->detach();

			$tag->delete();

			$this->success(__('Tag deleted successfully'));
			$this->loadTags();

			// Notify other components to refresh
			$this->dispatch('tags-updated');
		} catch (\Exception $e) {
			$this->error(__('Failed to delete tag: :error', ['error' => $e->getMessage()]));
		}
	}

	/**
	 * Render the component.
	 *
	 * @since 1.0.0
	 */
	public function render(): \Illuminate\View\View
	{
		return view('media::livewire.components.tag-manager');
	}
}
