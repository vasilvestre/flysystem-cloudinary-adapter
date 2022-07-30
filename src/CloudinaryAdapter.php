<?php

namespace Vasilvestre\Flysystem\Cloudinary;

use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Api\Search\SearchApi;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Asset\AssetType;
use Cloudinary\Cloudinary;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckFileExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;

class CloudinaryAdapter implements FilesystemAdapter
{
    public const ON_VISIBILITY_THROW_ERROR = 'throw';

    private UploadApi $uploadApi;
    private AdminApi $adminApi;
    private SearchApi $searchApi;
    private string $visibilityHandling;
    private ?string $uriPrefix;

    /**
     * @param array $options
     *                       string $cloud_name Your cloud name
     *                       string $api_key Your api key
     *                       string $api_secret You api secret
     */
    public function __construct(array $options = [], string $visibilityHandling = self::ON_VISIBILITY_THROW_ERROR)
    {
        $cloudinaryClient = new Cloudinary($options);
        $this->uploadApi = $cloudinaryClient->uploadApi();
        $this->adminApi = $cloudinaryClient->adminApi();
        $this->searchApi = $cloudinaryClient->searchApi();
        $this->visibilityHandling = $visibilityHandling;
        $this->uriPrefix = $options['uri_prefix'] ?? null;
    }

    private static function sanitizePath(string $path): string
    {
        return preg_replace('/(!|\(|\)|{|}|\[|\]|\*|\^|~|\?|:|\|=|&|>|<|\s)/m', '\\\\$1', $path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $options = [
            'public_id' => $this->uriPrefix . PathConverter::convertPathToPublicId($path),
            'overwrite' => true,
            'resource_type' => 'auto',
            'async' => $config->get('async'),
        ];

        try {
            $this->uploadApi->upload(new DataUri($contents), $options);
        } catch (ApiError $error) {
            throw new UnableToWriteFile($error->getMessage());
        }
    }

    /**
     * @param resource $contents
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        if ('stream' !== get_resource_type($contents)) {
            throw new \LogicException('Resource have to be a stream');
        }

        $options = [
            'public_id' => $this->uriPrefix . PathConverter::convertPathToPublicId($path),
            'overwrite' => true,
            'resource_type' => 'auto',
            'async' => $config->get('async'),
        ];

        try {
            $this->uploadApi->upload(new DataUri(stream_get_contents($contents)), $options);
        } catch (ApiError $error) {
            throw new UnableToWriteFile($error->getMessage());
        }
    }

    public function delete(string $path): void
    {
        $path = $this->uriPrefix . $path;
        $resources = $this->searchApi->expression(sprintf('public_id:%s', self::sanitizePath($path)))->execute()['resources'];
        if (false === empty($resources)) {
            try {
                $resource = $resources[0];
                $this->adminApi->deleteAssets([$resource['public_id']], ['resource_type' => $resource['resource_type']]);
            } catch (ApiError $error) {
                throw new UnableToDeleteFile($error->getMessage());
            }
        }
    }

    public function deleteDirectory(string $path): void
    {
        try {
            if (str_starts_with($path, $this->uriPrefix) === false) {
                $path = $this->uriPrefix . $path;
            }
            $this->adminApi->deleteAssetsByPrefix($path, ['resource_type' => AssetType::RAW]);
            $this->adminApi->deleteAssetsByPrefix($path, ['resource_type' => AssetType::IMAGE]);
            $this->adminApi->deleteAssetsByPrefix($path, ['resource_type' => AssetType::VIDEO]);
            $this->adminApi->deleteFolder($path);
        } catch (ApiError $error) {
            throw new UnableToDeleteDirectory($error->getMessage());
        }
    }

    public function move(string $source, string $destination, Config $config): void
    {
        try {
            $source = $this->uriPrefix . $source;
            $destination = $this->uriPrefix . $destination;
            $resources = $this->searchApi->expression(sprintf('public_id:%s', self::sanitizePath($source)))->execute()['resources'];
            if (empty($resources)) {
                throw new NotFound();
            }
            $resource = $resources[0];
            $this->uploadApi->rename($source, $destination, ['resource_type' => $resource['resource_type'], 'overwrite' => 'true']);
            $this->delete($source);
        } catch (NotFound $exception) {
            throw new UnableToMoveFile($exception->getMessage());
        }
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        throw UnableToCopyFile::fromLocationTo($source, $destination, new \LogicException('Cloudinary does not support copy'));
    }

    public function setVisibility(string $path, string $visibility): void
    {
        if (self::ON_VISIBILITY_THROW_ERROR === $this->visibilityHandling) {
            throw UnableToSetVisibility::atLocation($path, 'Cloudinary does not support this operation.');
        }
    }

    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'Cloudinary does not support visibility');
    }

    public function fileExists(string $path): bool
    {
        try {
            $path = $this->uriPrefix . $path;
            return false === empty($this->searchApi->expression(sprintf('public_id:%s', self::sanitizePath($path)))->execute()['resources']);
        } catch (\Exception $exception) {
            throw new UnableToCheckFileExistence($exception->getMessage());
        }
    }

    public function mimeType(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::mimeType($path, 'Cloudinary does not support MIME type');
    }

    public function lastModified(string $path): FileAttributes
    {
        try {
            return $this->getMetadata($path);
        } catch (NotFound $exception) {
            throw new UnableToRetrieveMetadata($exception->getMessage());
        }
    }

    public function fileSize(string $path): FileAttributes
    {
        try {
            return $this->getMetadata($path);
        } catch (NotFound $exception) {
            throw new UnableToRetrieveMetadata($exception->getMessage());
        }
    }

    public function createDirectory(string $path, Config $config): void
    {
        try {
            $path = $this->uriPrefix . $path;
            $this->adminApi->createFolder($path);
        } catch (ApiError $error) {
            throw new UnableToCreateDirectory($error->getMessage());
        }
    }

    public function directoryExists(string $path): bool
    {
        $path = $this->uriPrefix . $path;
        if (str_contains($path, '/')) {
            $dirs = $this->adminApi->subFolders(dirname($path))['folders'];
        } else {
            $dirs = $this->adminApi->rootFolders()['folders'];
        }
        if (in_array($path, array_column($dirs, 'path'), true)) {
            return true;
        }

        return false;
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $path = $this->uriPrefix . $path;
        foreach ($this->doListContents($path, $deep) as $file) {
            yield FileAttributes::fromArray($file);
        }

        $dirs = $this->adminApi->subFolders($path)['folders'];
        foreach ($dirs as $dir) {
            yield DirectoryAttributes::fromArray($dir);
        }
    }

    private function doListContents(string $directory = '', bool $deep = false, array $storage = ['files' => []])
    {
        $query = $this->searchApi->maxResults(500);
        if (!empty($directory)) {
            if (false === $deep) {
                $query->expression("folder:$directory");
            } else {
                $query->expression("folder:$directory/*");
            }
        }
        if (isset($storage['next_cursor'])) {
            $query = $query->nextCursor($storage['next_cursor']);
        }
        $response = $query->execute();

        foreach ($response['resources'] as $resource) {
            $storage['files'][] = $this->normalizeMetadata($resource);
        }
        if (isset($response['next_cursor'])) {
            $storage['next_cursor'] = $response['next_cursor'];

            return $this->doListContents($directory, $deep, $storage);
        }

        return $storage['files'];
    }

    /**
     * @throws NotFound
     * @throws \Cloudinary\Api\Exception\GeneralError
     */
    public function getMetadata(string $path): FileAttributes
    {
        $path = $this->uriPrefix . $path;
        $resources = $this->searchApi->expression(sprintf('public_id:%s', self::sanitizePath($path)))->execute()['resources'];
        if (empty($resources)) {
            throw new NotFound();
        }
        $resource = $resources[0];
        $resource['path'] = $resource['public_id'];

        return new FileAttributes(
            $path,
            $resource['bytes'],
            'true',
            strtotime($resource['created_at']),
            'file',
            [
                'path' => $resource['path'],
                'size' => $resource['bytes'] ?? false,
                'timestamp' => isset($resource['created_at']) ? strtotime($resource['created_at']) : false,
                'version' => $resource['version'] ?? 1,
            ]
        );
    }

    public function read(string $path): string
    {
        $response = $this->readStream($path);

        return stream_get_contents($response);
    }

    public function content($path)
    {
        return fopen($this->url($path), 'rb');
    }

    public function readStream(string $path)
    {
        return $this->content($path);
    }

    private function normalizeMetadata($resource): bool|array
    {
        return !$resource instanceof \ArrayObject && !is_array($resource) ? false : [
            'type' => 'file',
            'path' => $resource['public_id'],
            'size' => $resource['bytes'] ?? false,
            'timestamp' => isset($resource['created_at']) ? strtotime($resource['created_at']) : false,
            'version' => $resource['version'] ?? 1,
        ];
    }

    private function url(string $path)
    {
        $path = $this->uriPrefix . $path;
        $resources = $this->searchApi->expression(sprintf('public_id:%s', self::sanitizePath($path)))->execute()['resources'];
        if (0 === \count($resources)) {
            throw new UnableToReadFile();
        }

        return $resources[0]['url'];
    }
}
