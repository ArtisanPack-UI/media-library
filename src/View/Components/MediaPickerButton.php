<?php

namespace ArtisanPackUI\MediaLibrary\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Media Picker Button Blade Component
 *
 * A convenience component that renders a button to trigger the MediaPicker.
 * Can be used standalone or to wrap the MediaPicker Livewire component.
 *
 * @since   1.1.0
 */
class MediaPickerButton extends Component
{
    /**
     * Create a new component instance.
     *
     * @since 1.1.0
     *
     * @param  string  $context  Context identifier for the picker.
     * @param  string  $label  Button label text.
     * @param  string  $icon  Icon name for the button.
     * @param  string  $variant  Button variant (primary, secondary, outline, ghost).
     * @param  string  $size  Button size (xs, sm, md, lg).
     * @param  bool  $multiSelect  Whether to enable multi-select mode.
     * @param  int  $maxSelections  Maximum selections allowed (0 = unlimited).
     * @param  string  $acceptTypes  Accepted MIME types pattern.
     * @param  int  $loadCount  Items to load per batch.
     * @param  bool  $withPicker  Whether to include the MediaPicker component.
     */
    public function __construct(
        public string $context = '',
        public string $label = '',
        public string $icon = 'fas.image',
        public string $variant = 'outline',
        public string $size = 'md',
        public bool $multiSelect = false,
        public int $maxSelections = 0,
        public string $acceptTypes = '',
        public int $loadCount = 20,
        public bool $withPicker = true,
    ) {
        if ($this->label === '') {
            $this->label = __('Select Media');
        }
    }

    /**
     * Get the view that represents the component.
     *
     * @since 1.1.0
     */
    public function render(): View
    {
        return view('media::components.media-picker-button');
    }
}
