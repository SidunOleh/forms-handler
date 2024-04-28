<?php

namespace FormsHandler;

defined('ABSPATH') or die;

class UploadedFile
{
    private array $file;

    public function __construct(array $file)
    {
        $this->file = $file;
    }

    public function upload(): string|false
    {
        if ($this->file['error']) {
            return false;
        }

        $uploaded = wp_handle_upload($this->file, ['test_form' => false,]);

        if (isset($uploaded['error'])) {
            return false;
        }

        return $uploaded['file'];
    }
}