<?php
/**
 * Author: Xavier Au
 * Date: 10/2/2017
 * Time: 5:50 PM
 */

namespace Anacreation\Media\Helpers\Validator;


use Anacreation\Media\Helpers\ExtensionAndMimes;
use Illuminate\Http\File;

class Validator
{
    public function isValidImage(File $file): bool {
        // this give the ext from the upload file
        $clientFileExt = strtolower($file->getExtension());

        // this get the mime type from the file content
        $guessedFileMimeType = $file->getMimeType();

        // check the ext matched the content
        if (in_array($clientFileExt, array_keys(ExtensionAndMimes::IMAGE))) {
            $mimeTypes = ExtensionAndMimes::IMAGE[$clientFileExt];
            $mimeTypes = is_array($mimeTypes) ? $mimeTypes : [$mimeTypes];
            return in_array($guessedFileMimeType, $mimeTypes);
        }
        return false;
    }
}