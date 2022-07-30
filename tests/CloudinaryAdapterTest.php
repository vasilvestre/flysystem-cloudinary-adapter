<?php

namespace Vasilvestre\Flysystem\Cloudinary\Tests;

use League\Flysystem\AdapterTestUtilities\FilesystemAdapterTestCase;
use League\Flysystem\Config;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToReadFile;

class CloudinaryAdapterTest extends FilesystemAdapterTestCase
{
    private const URI_PREFIX = 'test/';

    protected static function createFilesystemAdapter(): FilesystemAdapter
    {
        return new OverridenCloudinaryAdapter([
            'cloud_name' => $_ENV["CLOUD_NAME"],
            'api_key' => $_ENV["API_KEY"],
            'api_secret' => $_ENV["API_SECRET"],
            'uri_prefix' => self::URI_PREFIX,
        ]);
    }
    /**
     * @test
     */
    public function listing_a_toplevel_directory(): void
    {
        self::markTestSkipped('Behaviour is inconsistent, the test run alone works');
    }

    /**
     * @test
     */
    public function listing_contents_recursive(): void
    {
        self::markTestSkipped('Behaviour is inconsistent, the test run alone works');
    }

    /**
     * @test
     */
    public function writing_and_reading_with_string(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();

            $adapter->write('path.txt', 'contents', new Config(['async' => false]));
            $fileExists = $adapter->fileExists('path.txt');
            $contents = $adapter->read('path.txt');

            $this->assertTrue($fileExists);
            $this->assertEquals('contents', $contents);
        });
    }

    /**
     * @test
     */
    public function writing_a_file_with_a_stream(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $writeStream = stream_with_contents('contents');

            $adapter->writeStream('path.txt', $writeStream, new Config(['async' => false]));

            if (is_resource($writeStream)) {
                fclose($writeStream);
            }

            $fileExists = $adapter->fileExists('path.txt');

            $this->assertTrue($fileExists);
        });
    }

    /**
     * @test
     * @dataProvider filenameProvider
     */
    public function writing_and_reading_files_with_special_path(string $path): void
    {
        $this->runScenario(function () use ($path) {
            $adapter = $this->adapter();

            $adapter->write($path, 'contents', new Config(['async' => false]));
            $contents = $adapter->read($path);

            $this->assertEquals('contents', $contents);
        });
    }

    /**
     * @test
     *
     * This one is modified because Cloudinary don't handle visibility
     */
    public function overwriting_a_file(): void
    {
        $this->runScenario(function () {
            $this->givenWeHaveAnExistingFile('path.txt');
            $adapter = $this->adapter();

            $adapter->write('path.txt', 'new contents', new Config(['async' => false]));

            $contents = $adapter->read('path.txt');
            $this->assertEquals('new contents', $contents);
        });
    }

    protected function givenWeHaveAnExistingFile(string $path, string $contents = 'contents', array $config = ['async' => false]): void
    {
        $this->runSetup(function () use ($path, $contents, $config) {
            $this->adapter()->write($path, $contents, new Config($config));
        });
    }

    /**
     * @test
     */
    public function moving_a_file(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();
            $adapter->write(
                'source.txt',
                'contents to be copied',
                new Config()
            );
            $adapter->move('source.txt', 'destination.txt', new Config());
            $this->assertFalse(
                $adapter->fileExists('source.txt'),
                'After moving a file should no longer exist in the original location.'
            );
            $this->assertTrue(
                $adapter->fileExists('destination.txt'),
                'After moving, a file should be present at the new location.'
            );
            $this->assertEquals('contents to be copied', $adapter->read('destination.txt'));
        });
    }

    /**
     * @test
     */
    public function creating_a_directory(): void
    {
        $this->runScenario(function () {
            $adapter = $this->adapter();

            $adapter->createDirectory('creating_a_directory/path', new Config());

            // Creating a directory should be idempotent.
            $adapter->createDirectory('creating_a_directory/path', new Config());

            $contents = iterator_to_array($adapter->listContents('creating_a_directory', false));
            $this->assertCount(1, $contents, $this->formatIncorrectListingCount($contents));
            /** @var DirectoryAttributes $directory */
            $directory = $contents[0];
            $this->assertInstanceOf(DirectoryAttributes::class, $directory);
            $this->assertEquals(self::URI_PREFIX .'creating_a_directory/path', $directory->path());
            $adapter->deleteDirectory('creating_a_directory/path');
        });
    }

    /**
     * @test
     */
    public function listing_contents_shallow(): void
    {
        $this->runScenario(function () {
            $this->givenWeHaveAnExistingFile('some/0-path.txt', 'contents');
            $this->givenWeHaveAnExistingFile('some/1-nested/path.txt', 'contents');

            $listing = $this->adapter()->listContents('some', false);
            /** @var StorageAttributes[] $items */
            $items = iterator_to_array($listing);

            $this->assertInstanceOf(\Generator::class, $listing);
            $this->assertContainsOnlyInstancesOf(StorageAttributes::class, $items);

            $this->assertCount(2, $items, $this->formatIncorrectListingCount($items));

            // Order of entries is not guaranteed
            [$fileIndex, $directoryIndex] = $items[0]->isFile() ? [0, 1] : [1, 0];

            $this->assertEquals(self::URI_PREFIX . 'some/0-path.txt', $items[$fileIndex]->path());
            $this->assertEquals(self::URI_PREFIX . 'some/1-nested', $items[$directoryIndex]->path());
            $this->assertTrue($items[$fileIndex]->isFile());
            $this->assertTrue($items[$directoryIndex]->isDir());
        });
    }

    /**
     * @test
     * @depends deleting_a_file
     */
    public function reading_a_file_that_does_not_exist(): void
    {
        $this->expectException(UnableToReadFile::class);

        $this->runScenario(function () {
            $this->adapter()->read('path.txt');
        });
    }

    /**
     * @test
     */
    public function copying_a_file(): void
    {
        self::markTestSkipped('Cloudinary does not support copy');
    }

    /**
     * @test
     */
    public function copying_a_file_with_collision(): void
    {
        self::markTestSkipped('Cloudinary does not support copy');
    }

    /**
     * @test
     */
    public function copying_a_file_again(): void
    {
        self::markTestSkipped('Cloudinary does not support copy');
    }

    /**
     * @test
     */
    public function fetching_unknown_mime_type_of_a_file(): void
    {
        self::markTestSkipped('Cloudinary does not support MIME type');
    }

    /**
     * @test
     */
    public function fetching_mime_type_of_non_existing_file(): void
    {
        self::markTestSkipped('Cloudinary does not support MIME type');
    }

    /**
     * @test
     */
    public function fetching_the_mime_type_of_an_svg_file(): void
    {
        self::markTestSkipped('Cloudinary does not support MIME type');
    }

    /**
     * @test
     */
    public function writing_a_file_with_an_empty_stream(): void
    {
        self::markTestSkipped('Cloudinary does not support empty files');
    }

    /**
     * @test
     */
    public function setting_visibility_on_a_file_that_does_not_exist(): void
    {
        self::markTestSkipped('Cloudinary does not support visibility');
    }

    /**
     * @test
     */
    public function fetching_visibility_of_non_existing_file(): void
    {
        self::markTestSkipped('Cloudinary does not support visibility');
    }

    /**
     * @test
     */
    public function setting_visibility(): void
    {
        self::markTestSkipped('Cloudinary does not support visibility');
    }
}
