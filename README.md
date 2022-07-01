# flysystem-cloudinary-adapter
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![CI](https://github.com/vasilvestre/flysystem-cloudinary-adapter/actions/workflows/php.yml/badge.svg)](https://github.com/vasilvestre/flysystem-cloudinary-adapter/actions/workflows/php.yml)

This is a [Flysystem adapter](https://github.com/thephpleague/flysystem) for [Cloudinary API](http://cloudinary.com/documentation/php_integration).

Highly inspired by [Enl/Flysystem-cloudinary](https://github.com/enl/flysystem-cloudinary).
I wanted to support flysystem 3+ and I have no response from owner but I can backport the work done here.

## Installation

```
composer require vasilvestre/flysystem-cloudinary-adapter
```

## Bootstrap

``` php
<?php
use Vasilvestre\Flysystem\Cloudinary\CloudinaryAdapter;
use League\Flysystem\Filesystem;

include __DIR__ . '/vendor/autoload.php';

$adapter = new CloudinaryAdapter([
    'cloud_name' => 'your-cloudname-here',
    'api_key' => 'api-key',
    'api_secret' => 'You-know-what-to-do'
]);

// I'm not sure about underlying instructions.

// This option disables assert that file is absent before calling `write`.
// It is necessary if you want to overwrite files on `write` as Cloudinary does it by default.
$filesystem = new Filesystem($adapter, ['disable_asserts' => true]);
```
