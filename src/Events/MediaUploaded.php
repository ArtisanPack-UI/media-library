<?php

/**
 * MediaUploaded Event
 *
 * Dispatched after a Media record has been created from an upload and
 * any caller-supplied tags have been attached. Provides a queueable
 * Laravel event surface for downstream packages (e.g. queued alt-text
 * generation) that need to run work asynchronously after upload.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Events
 *
 * @since      1.5.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\MediaLibrary\Events;

use ArtisanPackUI\MediaLibrary\Models\Media;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after a Media record has been persisted from an upload.
 *
 * Companion to the synchronous `ap.mediaLibrary.uploaded` hook. Use this
 * event when a subscriber needs to implement `ShouldQueue` so the work
 * runs on a queue worker rather than in the upload request lifecycle.
 *
 * @since 1.5.0
 */
class MediaUploaded
{
    use Dispatchable;
    use SerializesModels;

    /**
     * The freshly created media record.
     *
     * @since 1.5.0
     *
     * @var Media
     */
    public Media $media;

    /**
     * Create a new event instance.
     *
     * @since 1.5.0
     *
     * @param Media $media The freshly created media record.
     */
    public function __construct( Media $media )
    {
        $this->media = $media;
    }
}
