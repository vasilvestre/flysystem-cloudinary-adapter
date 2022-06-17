<?php

namespace Vasilvestre\Flysystem\Cloudinary\Exception;

use Throwable;

class NotImplementedException extends \Exception
{
    public function __construct($message = 'Not implemented yet.', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
