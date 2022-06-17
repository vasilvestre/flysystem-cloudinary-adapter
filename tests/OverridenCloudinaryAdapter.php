<?php

namespace Vasilvestre\Flysystem\Cloudinary\Tests;

use League\Flysystem\Config;
use Vasilvestre\Flysystem\Cloudinary\CloudinaryAdapter;

class OverridenCloudinaryAdapter extends CloudinaryAdapter
{
    public function write(string $path, string $contents, Config $config): void
    {
        parent::write($path, $contents, $config);
        sleep(3);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        parent::writeStream($path, $contents, $config);
        sleep(3);
    }

    public function delete(string $path): void
    {
        parent::delete($path);
        sleep(3);
    }


}