<?php

namespace Vasilvestre\Flysystem\Cloudinary;

use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Cloudinary;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use Cloudinary\Api\Upload\UploadApi;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToWriteFile;

class CloudinaryAdapter implements FilesystemAdapter
{
    private UploadApi $uploadApi;
    private AdminApi $adminApi;

    public function __construct()
    {
        $this->uploadApi = new UploadApi();
        $this->adminApi = new AdminApi();
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $options = [
            'public_id' => $this->pathToPublicId($path),
            'overwrite' => true,
        ];

        $this->uploadApi->upload(new DataUri($contents), $options);
    }

    /**
     * @param resource $contents
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        if (get_resource_type($contents) !== "stream") { throw new \LogicException("Resource have to be a stream"); }

        $options = [
            'public_id' => $this->pathToPublicId($path),
            'overwrite' => true,
        ];

        $this->uploadApi->upload(new DataUri(stream_get_contents($contents)), $options);
    }

    public function rename(string $path, string $newPath): void
    {
        $this->uploadApi->rename(
            $this->pathToPublicId($path),
            $this->pathToPublicId($newPath)
        );
    }

    public function pathToPublicId(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return $extension
            ? substr($path, 0, - (strlen($extension) + 1))
            : $path;
    }

    public function delete(string $path): void
    {
        $response = $this->uploadApi->destroy($this->pathToPublicId($path));
        if ($response["result"] !== "ok") {
            throw new \Exception("Couldn't delete asset");
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            $response = $this->adminApi->deleteFolder($path);
        } catch (ApiError $error) {
            throw new \Exception("Couldn't delete folder");
        }

        if (!in_array($path, $response["deleted"], true)) {
            throw new \Exception("Couldn't delete folder");
        }
    }
}