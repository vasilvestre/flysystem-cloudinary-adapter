<?php

namespace Vasilvestre\Flysystem\Cloudinary;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\FilesystemAdapter;

class CloudinaryAdapterTest extends FilesystemAdapterTestCase
{
    /**
     * @dataProvider testPathToPublicIdDatas
     */
    public function testPathToPublicId($expectedResult, $input): void
    {
        $this->assertEquals($expectedResult, static::$adapter->pathToPublicId($input));
    }

    public function testPathToPublicIdDatas(): array
    {
        return [
            [
                'myfolder/mysubfolder/my_asset_name.jpg',
                'myfolder/mysubfolder/my_asset_name'
            ]
        ];
    }

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        static::$adapter = new CloudinaryAdapter();
    }
}
