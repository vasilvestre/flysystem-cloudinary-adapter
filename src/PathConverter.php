<?php

namespace Vasilvestre\Flysystem\Cloudinary;

use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

class PathConverter
{
    public static function convertPathToPublicId(string $path): string
    {
        $mimeType = (new GeneratedExtensionToMimeTypeMap())->lookupMimeType(pathinfo($path, PATHINFO_EXTENSION));

        switch ($mimeType) {
            case str_starts_with($mimeType, 'video'):
            case str_starts_with($mimeType, 'image'):
            case str_starts_with($mimeType, 'audio'):
                return pathinfo($path, PATHINFO_FILENAME);
            default:
                return $path;
        }
    }
}
