<?php

class Kudja_Tinify_Model_Service_Converter
{
    /**
     * @var string
     */
    protected $baseDir;

    /**
     * @var Kudja_Tinify_Helper_Data
     */
    protected $helper;

    public function __construct()
    {
        $this->baseDir = Mage::getBaseDir();
        $this->helper = Mage::helper('tinify/data');
    }

    /**
     * Convert image to WebP
     *
     * @param string      $relativePath
     * @param string      $method 'tinify_api' or 'cwebp'
     * @param string|null $apiKey
     * @param string|null $cwebpCommand
     * @return bool
     */
    public function convert(string $relativePath, string $method, $apiKey = null, $cwebpCommand = null): bool
    {
        $src = $this->resolveSourcePath($relativePath);
        if (!is_readable($src)) {
            return $this->logError("File not readable: $src");
        }

        $target = $src . '.webp';
        if (file_exists($target)) {
            return true;
        }

        try {
            if ($method === 'cwebp') {
                $result = $this->convertWithCwebp($src, $target, $cwebpCommand);
            } elseif ($method === 'tinify_api') {
                $result = $this->convertWithTinify($src, $target, $apiKey);
            } else {
                return $this->logError("Unknown method: $method");
            }
        } catch (Exception $e) {
            return $this->logError($e->getMessage());
        }

        if (!$result) {
            return false;
        }

        if (filesize($target) > filesize($src)) {
            @unlink($target);
            return $this->logError("Converted file is larger than original: $src");
        }

        return true;
    }

    /**
     * Convert image using Tinify API
     *
     * @param string $src
     * @param string $target
     * @param string $apiKey
     * @return bool
     */
    protected function convertWithTinify(string $src, string $target, string $apiKey): bool
    {
        \Tinify\setKey($apiKey);
        $source = \Tinify\fromFile($src);
        $source->toFile($target);
        $converted = $source->convert(["type" => "image/webp"]);
        $converted->toFile($target);

        if (!file_exists($target)) {
            throw new RuntimeException("Failed to convert image with Tinify");
        }

        return true;
    }

    /**
     * Convert image using cwebp command
     *
     * @param string $src
     * @param string $target
     * @param string $command
     * @return bool
     */
    protected function convertWithCwebp(string $src, string $target, string $command): bool
    {
        if (!$command) {
            throw new RuntimeException("cwebp command not set");
        }

        $exec = escapeshellcmd($command) . ' ' . escapeshellarg($src) . ' -o ' . escapeshellarg($target);
        exec($exec, $output, $code);

        if ($code !== 0) {
            throw new RuntimeException("cwebp failed: " . implode("\n", $output));
        }

        return file_exists($target);
    }

    /**
     * Resolve the full path of the source file
     *
     * @param string $relativePath
     * @return string
     */
    protected function resolveSourcePath(string $relativePath): string
    {
        return $this->baseDir . DS . ltrim($relativePath, '/');
    }

    /**
     * Log an error message
     *
     * @param string $message
     * @return bool
     */
    protected function logError(string $message): bool
    {
        Mage::log(str_replace($this->baseDir, '', $message), Zend_Log::ERR, 'tinify.log');
        return false;
    }
}
