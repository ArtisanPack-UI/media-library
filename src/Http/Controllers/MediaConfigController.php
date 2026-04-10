<?php

/**
 * Media Config Controller
 *
 * Exposes the media library upload configuration via a public API endpoint
 * so React/Vue frontend components can validate uploads client-side.
 *
 * @package    ArtisanPack_UI
 * @subpackage MediaLibrary\Http\Controllers
 *
 * @since      1.2.0
 */

declare( strict_types=1 );

namespace ArtisanPackUI\MediaLibrary\Http\Controllers;

use ArtisanPackUI\MediaLibrary\Managers\MediaManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Symfony\Component\Mime\MimeTypes;

/**
 * Media Config Controller
 *
 * Returns the server-side upload configuration (max file size, allowed MIME types,
 * allowed extensions, image sizes, and feature flags) for client-side validation.
 *
 * @since   1.2.0
 */
class MediaConfigController extends Controller
{
    /**
     * Return the media upload configuration.
     *
     * @since 1.2.0
     *
     * @param  MediaManager  $manager  The media manager instance.
     *
     * @return JsonResponse The media configuration response.
     */
    public function __invoke( MediaManager $manager ): JsonResponse
    {
        $maxFileSizeKb = $manager->getMaxFileSize();
        $mimeTypes     = $manager->getAllowedMimeTypes();

        $data = [
            'upload' => [
                'max_file_size'       => $maxFileSizeKb,
                'max_file_size_human' => $this->humanFileSize( $maxFileSizeKb * 1024 ),
                'allowed_mime_types'  => $this->groupMimeTypes( $mimeTypes ),
                'allowed_extensions'  => $this->extractExtensions( $mimeTypes ),
            ],
            'image_sizes' => $manager->getImageSizes(),
            'features'    => [
                'webp_conversion' => $manager->isModernFormatsEnabled() && 'webp' === $manager->getModernFormat(),
                'avif_conversion' => $manager->isModernFormatsEnabled() && 'avif' === $manager->getModernFormat(),
            ],
        ];

        $etag = '"' . md5( (string) json_encode( $data ) ) . '"';

        return response()->json( $data )
            ->header( 'Cache-Control', 'public, s-maxage=3600, max-age=3600, stale-while-revalidate=86400' )
            ->header( 'ETag', $etag );
    }

    /**
     * Group MIME types by their category (image, video, audio, document).
     *
     * @since 1.2.0
     *
     * @param  array<int, string>  $mimeTypes  The flat list of allowed MIME types.
     *
     * @return array<string, array<int, string>> MIME types grouped by category.
     */
    protected function groupMimeTypes( array $mimeTypes ): array
    {
        $grouped = [];

        foreach ( $mimeTypes as $mime ) {
            $category = explode( '/', $mime )[0] ?? 'other';

            // Map application/* types to 'document'
            if ( 'application' === $category || 'text' === $category ) {
                $category = 'document';
            }

            $grouped[ $category ][] = $mime;
        }

        return $grouped;
    }

    /**
     * Extract file extensions from MIME types.
     *
     * @since 1.2.0
     *
     * @param  array<int, string>  $mimeTypes  The flat list of allowed MIME types.
     *
     * @return array<int, string> Unique file extensions.
     */
    protected function extractExtensions( array $mimeTypes ): array
    {
        $mimeToExtension = [
            'image/jpeg'                                                              => ['jpg', 'jpeg'],
            'image/jpg'                                                               => ['jpg'],
            'image/png'                                                               => ['png'],
            'image/gif'                                                               => ['gif'],
            'image/webp'                                                              => ['webp'],
            'image/avif'                                                              => ['avif'],
            'image/svg+xml'                                                           => ['svg'],
            'application/pdf'                                                         => ['pdf'],
            'application/msword'                                                      => ['doc'],
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
            'application/vnd.ms-excel'                                                => ['xls'],
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => ['xlsx'],
            'video/mp4'                                                               => ['mp4'],
            'video/mpeg'                                                              => ['mpeg'],
            'video/quicktime'                                                         => ['mov'],
            'video/webm'                                                              => ['webm'],
            'audio/mpeg'                                                              => ['mp3'],
            'audio/wav'                                                               => ['wav'],
            'audio/ogg'                                                               => ['ogg'],
            'text/plain'                                                              => ['txt'],
        ];

        $extensions    = [];
        $mimeGuesser   = MimeTypes::getDefault();

        foreach ( $mimeTypes as $mime ) {
            if ( isset( $mimeToExtension[ $mime ] ) ) {
                $extensions = array_merge( $extensions, $mimeToExtension[ $mime ] );
            } else {
                $guessed    = $mimeGuesser->getExtensions( $mime );
                $extensions = array_merge( $extensions, $guessed );
            }
        }

        return array_values( array_unique( $extensions ) );
    }

    /**
     * Convert bytes to a human-readable file size string.
     *
     * @since 1.2.0
     *
     * @param  int  $bytes  The file size in bytes.
     *
     * @return string The human-readable file size.
     */
    protected function humanFileSize( int $bytes ): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ( $i = 0; $bytes >= 1024 && $i < count( $units ) - 1; $i++ ) {
            $bytes /= 1024;
        }

        return round( $bytes, 1 ) . ' ' . $units[ $i ];
    }
}
