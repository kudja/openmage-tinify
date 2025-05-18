<?php

class Kudja_Tinify_Model_Service_Converter
{

    /**
     * @var string|null
     */
    protected ?string $baseDir;

    public function __construct()
    {
        $this->baseDir = Mage::getBaseDir();
    }

    /**
     * Convert image to WebP
     *
     * @param string      $relativePath
     * @param string      $method
     * @param string|null $apiKey
     * @param string|null $cwebpCommand
     *
     * @return bool
     */
    public function convert(string $relativePath, string $method, ?string $apiKey = null, ?string $cwebpCommand = null): bool
    {
        $src = $this->baseDir . DS . ltrim($relativePath, '/');

        if (!is_readable($src)) {
            Mage::log("File not found: $src", Zend_Log::ERR, 'tinify.log');
            return false;
        }

        $target = $src . '.webp';

        try {
            if ($method === 'cwebp') {
                if (!$cwebpCommand) {
                    throw new RuntimeException("Cwebp command is not set");
                }

                exec(str_replace(
                         ['{src}', '{target}'],
                         [escapeshellarg($src), escapeshellarg($target)],
                         $cwebpCommand
                     ));

            } elseif ($method === 'tinify_api') {
                if (!$apiKey) {
                    throw new RuntimeException("Tinify API key is not provided");
                }

                \Tinify\setKey($apiKey);
                $source = \Tinify\fromFile($src);
                $source->toFile($src);
                $converted = $source->convert(["type" => "image/webp"]);
                $converted->toFile($target);
            } else {
                throw new InvalidArgumentException("Unknown conversion method: $method");
            }

            if (!file_exists($target)) {
                Mage::log("Conversion failed (file not created): $src", Zend_Log::ERR, 'tinify.log');
                return false;
            }

            if (filesize($target) >= filesize($src)) {
                @unlink($target);
                Mage::log("Converted file is larger than original: $src", Zend_Log::WARN, 'tinify.log');
                return false;
            }

            return true;

        } catch (Exception $e) {
            Mage::log(
                "Conversion error [Path: $relativePath]: {$e->getMessage()}",
                Zend_Log::ERR,
                'tinify.log'
            );
            return false;
        }
    }

}
