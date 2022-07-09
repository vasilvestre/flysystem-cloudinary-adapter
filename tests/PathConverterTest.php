<?php

namespace Vasilvestre\Flysystem\Cloudinary\Tests;

use Vasilvestre\Flysystem\Cloudinary\PathConverter;
use PHPUnit\Framework\TestCase;

class PathConverterTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testConvertPathToPublicId(string $path, string $expectedResult): void
    {
        $this->assertEquals($expectedResult, PathConverter::convertPathToPublicId($path));
    }

    /**
     * @return array<array<string>>
     */
    public function provider(): array
    {
        return [
            ['foo.jpg', 'foo'],
            ['test.jpg.jpg', 'test.jpg'],
            ['bar.avif', 'bar'],
            ['foobar.txt', 'foobar.txt'],
            ['test/foobar.txt', 'test/foobar.txt'],
            ['test/foo_bar.txt', 'test/foo_bar.txt'],
            ['test/foo bar.txt', 'test/foo bar.txt'],
        ];
    }
}
