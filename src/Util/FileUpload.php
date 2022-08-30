<?php

namespace Codefog\HasteBundle\Util;

use Contao\Config;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FileUpload extends \Contao\FileUpload
{
    protected bool $doNotOverwrite = false;
    protected array $extensions = [];
    protected int $minFileSize = 0;
    protected int $maxFileSize;
    protected int $imageWidth;
    protected int $imageHeight;
    protected int $gdMaxImgWidth;
    protected int $gdMaxImgHeight;

    /**
     * Temporary store target from uploadTo() to make it available to getFilesFromGlobal()
     */
    private string $target;

    public function __construct(string $name)
    {
        parent::__construct();

        $this->setName($name);

        $this->extensions = StringUtil::trimsplit(',', strtolower(Config::get('uploadTypes')));
        $this->maxFileSize = Config::get('maxFileSize');
        $this->imageWidth = Config::get('imageWidth');
        $this->imageHeight = Config::get('imageHeight');
        $this->gdMaxImgWidth = Config::get('gdMaxImgWidth');
        $this->gdMaxImgHeight = Config::get('gdMaxImgHeight');
    }

    public function getName()
    {
        return $this->strName;
    }

    public function isDoNotOverwrite(): bool
    {
        return $this->doNotOverwrite;
    }

    public function setDoNotOverwrite(bool $doNotOverwrite): self
    {
        $this->doNotOverwrite = $doNotOverwrite;

        return $this;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function setExtensions(array $extensions): self
    {
        $this->extensions = array_map('strtolower', $extensions);

        return $this;
    }

    public function addExtension(string $extension): self
    {
        $this->extensions[] = strtolower($extension);

        return $this;
    }

    public function getMinFileSize(): int
    {
        return $this->minFileSize;
    }

    public function setMinFileSize(int $minFileSize): self
    {
        $this->minFileSize = $minFileSize;

        return $this;
    }

    public function getMaxFileSize(): int
    {
        return $this->maxFileSize;
    }

    public function setMaxFileSize(int $maxFileSize): self
    {
        $this->maxFileSize = $maxFileSize;

        return $this;
    }

    public function getImageWidth(): int
    {
        return $this->imageWidth;
    }

    public function setImageWidth(int $imageWidth): self
    {
        $this->imageWidth = $imageWidth;

        return $this;
    }

    public function getImageHeight(): int
    {
        return $this->imageHeight;
    }

    public function setImageHeight(int $imageHeight): self
    {
        $this->imageHeight = $imageHeight;

        return $this;
    }

    public function getGdMaxImgWidth(): int
    {
        return $this->gdMaxImgWidth;
    }

    public function setGdMaxImgWidth(int $gdMaxImgWidth): self
    {
        $this->gdMaxImgWidth = $gdMaxImgWidth;

        return $this;
    }

    public function getGdMaxImgHeight(): int
    {
        return $this->gdMaxImgHeight;
    }

    public function setGdMaxImgHeight(int $gdMaxImgHeight): self
    {
        $this->gdMaxImgHeight = $gdMaxImgHeight;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function uploadTo($target): array
    {
        $this->target = $target;

        // Preserve the configuration
        $uploadTypes = Config::get('uploadTypes');
        Config::set('uploadTypes', implode(',', $this->extensions));

        $filesizeLabel = $GLOBALS['TL_LANG']['ERR']['filesize'];
        $GLOBALS['TL_LANG']['ERR']['filesize'] = $GLOBALS['TL_LANG']['ERR']['maxFileSize'];

        // Perform upload
        $result = parent::uploadTo($target);

        // Restore the configuration
        Config::set('uploadTypes', $uploadTypes);
        $GLOBALS['TL_LANG']['ERR']['filesize'] = $filesizeLabel;

        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function getFilesFromGlobal(): array
    {
        if (is_array($_FILES[$this->strName]['name'] ?? null)) {
            $files = parent::getFilesFromGlobal();
        } else {
            $files = [$_FILES[$this->strName]];
        }

        if ($this->doNotOverwrite) {
            foreach ($files as $k => $file) {
                $files[$k]['name'] = static::getFileName($file['name'], $this->target);
            }
        }

        // Validate minimum file size and skip from parent call
        if ($this->minFileSize > 0) {
            $minlength_kb_readable = static::getReadableSize($this->minFileSize);

            foreach ($files as $k => $file) {
                if (!$file['error'] && $file['size'] < $this->minFileSize) {
                    Message::addError(sprintf($GLOBALS['TL_LANG']['ERR']['minFileSize'], $minlength_kb_readable));
                    $this->blnHasError = true;
                    unset($files[$k]);
                }
            }
        }

        return $files;
    }

    /**
     * @inheritdoc
     */
    protected function resizeUploadedImage($strImage)
    {
        $imageWidth = Config::get('imageWidth');
        $imageHeight = Config::get('imageHeight');
        $gdMaxImgWidth = Config::get('gdMaxImgWidth');
        $gdMaxImgHeight = Config::get('gdMaxImgHeight');

        Config::set('imageWidth', $this->imageWidth);
        Config::set('imageHeight', $this->imageHeight);
        Config::set('gdMaxImgWidth', $this->gdMaxImgWidth);
        Config::set('gdMaxImgHeight', $this->gdMaxImgHeight);

        $return = parent::resizeUploadedImage($strImage);

        Config::set('imageWidth', $imageWidth);
        Config::set('imageHeight', $imageHeight);
        Config::set('gdMaxImgWidth', $gdMaxImgWidth);
        Config::set('gdMaxImgHeight', $gdMaxImgHeight);

        return $return;
    }

    /**
     * Get the new file name if it already exists in the folder.
     */
    public static function getFileName(string $uploadedFile, string $uploadFolder): string
    {
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        if (!file_exists($projectDir . '/' . $uploadFolder . '/' . $uploadedFile)) {
            return $uploadedFile;
        }

        $offset = 1;
        $pathinfo = pathinfo($uploadedFile);
        $name = $pathinfo['filename'];

        /** @var SplFileInfo[] $files */
        $files = Finder::create()
            ->in($projectDir . '/' . $uploadFolder)
            ->files()
            ->name('/^' . preg_quote($name, '/') . '.*\.' . preg_quote($pathinfo['extension'], '/') . '/')
        ;

        foreach ($files as $file) {
            $fileName = $file->getFilename();

            if (preg_match('/__[0-9]+\.' . preg_quote($pathinfo['extension'], '/') . '$/', $fileName)) {
                $fileName = str_replace('.' . $pathinfo['extension'], '', $fileName);
                $value = (int) substr($fileName, (strrpos($fileName, '_') + 1));
                $offset = max($offset, $value);
            }
        }

        return str_replace($name . '.', $name . '__' . ++$offset . '.', $uploadedFile);
    }
}
