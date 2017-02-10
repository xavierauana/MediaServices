<?php
/**
 * Author: Xavier Au
 * Date: 11/1/2017
 * Time: 7:18 PM
 */

namespace Anacreation\Media\Service;


use Anacreation\Media\Model\Media;
use Illuminate\Http\UploadedFile;

class MediaServices
{
    private $typeMap = [
        'image' => ['jpg', 'png', 'gif', 'jpeg'],
        'audio' => ['mp3'],
        'video' => ['mp4']
    ];
    /**
     * @var null
     */
    private $path;

    /**
     * MediaServices constructor.
     * @param array $typeMap
     */
    public function __construct($relative_path = null) {
        $this->path = $relative_path?? null;
    }


    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param null                          $path
     * @return \Anacreation\Media\Model\Media
     * @throws \Exception
     */
    public function save(UploadedFile $file) {

        //get media type
        $type = $this->getMediaType($file);

        //rename the file
        $hashed_file_name = $this->hashName($file);

        //compute path and link
        list($relativePath, $path, $link) = $this->getFileLinkAndPath($type, $hashed_file_name);


        //persist or retrieve in db
        return $this->getMediaObject($file, $path, $link, $hashed_file_name, $type, $relativePath);

    }

    private function hashName($file) {
        return md5_file($file->getRealPath()) . '.' . $file->getClientOriginalExtension();
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     * @throws \Exception
     */
    private function getMediaType(UploadedFile $file): string {
        $type = null;
        $mimeType = $file->getClientMimeType();
        $_type = strtolower(substr($mimeType, 0, strpos($mimeType, '/')));
        if (in_array($_type, array_keys($this->typeMap))) {
            $type = $_type;
        } else {
            $ext = $file->getClientOriginalExtension();
            foreach ($this->typeMap as $key => $extensionArray) {
                if (in_array($ext, $extensionArray)) {
                    $type = $key;
                    break;
                }
            }
        }

        if (!$type) {
            throw new \Exception("Uploaded file type is not allowed!");
        }

        return $type;
    }

    /**
     * @param $path
     * @param $dbCompatibleType
     * @param $hashed_file_name
     * @return array
     */
    private function getFileLinkAndPath($dbCompatibleType, $hashed_file_name): array {
        $relativePath = "upload/" . str_plural($dbCompatibleType);
        $path = $this->path ?? public_path($relativePath);
        $link = asset($relativePath . "/" . $hashed_file_name);

        return array($relativePath, $path, $link);
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param                               $path
     * @param                               $link
     * @param                               $hashed_file_name
     * @param                               $dbCompatibleType
     * @param                               $relativePath
     * @return \Anacreation\Media\Model\Media
     */
    private function getMediaObject(UploadedFile $file, $path, $link, $hashed_file_name, $dbCompatibleType,
        $relativePath): \Anacreation\Media\Model\Media {
        if ($media = Media::whereLink($link)->first()) {
            return $media;
        } else {
            //move to location
            return $this->persistNewMedia($file, $path, $hashed_file_name, $dbCompatibleType, $relativePath);
        }
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param                               $path
     * @param                               $hashed_file_name
     * @param                               $dbCompatibleType
     * @param                               $relativePath
     * @return \Anacreation\Media\Model\Media
     */
    private function persistNewMedia(UploadedFile $file, $path, $hashed_file_name, $dbCompatibleType,
        $relativePath): \Anacreation\Media\Model\Media {
        $file->move($path, $hashed_file_name);

        //persist in db
        $newMedia = new Media();
        $newMedia->type = $dbCompatibleType;
        $newMedia->link = asset($relativePath . "/" . $hashed_file_name);
        $newMedia->file_name = $file->getClientOriginalName();
        $newMedia->save();

        return $newMedia;
    }
}