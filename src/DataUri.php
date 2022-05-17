<?php

namespace Vasilvestre\Flysystem\Cloudinary;

/**
 * Creates DATA-URI formatted string from file content.
 *
 * @author Alex Panshin <deadyaga@gmail.com>
 */
class DataUri
{
    private string $content;

    private \finfo $finfo;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    private function getFileInfo(): \finfo
    {
        if (!$this->finfo) {
            $this->finfo = new \finfo(FILEINFO_MIME_TYPE);
        }

        return $this->finfo;
    }

    public function __toString(): string
    {
        return sprintf(
            'data:%s;base64,%s',
            $this->getFileInfo()->buffer($this->content),
            base64_encode($this->content)
        );
    }
}