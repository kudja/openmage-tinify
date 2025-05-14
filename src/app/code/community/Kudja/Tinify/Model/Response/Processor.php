<?php

class Kudja_Tinify_Model_Response_Processor
{
    /** @var string */
    protected string $baseDir;

    /** @var string */
    protected string $baseUrl;

    /** @var string */
    protected string $scheme;

    /** @var array */
    protected array $batchPaths = [];

    /** @var array */
    protected array $fileExistsCache = [];

    protected string $tagsPattern = "/<(?P<tag>{tags}[^>]*)[^>]+\.(jpe?g|png)(?!\.webp)[^>]*>/i";

    protected string $attributesPattern = "/({attributes})\s*=\s*(['\"])([^\\2]+?)\\2/i";
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->baseDir = Mage::getBaseDir();
        $this->baseUrl = str_replace('media/', '', Mage::getBaseUrl('media'));
        $this->scheme  = Mage::app()->getRequest()->getScheme();

        /** @var Kudja_Tinify_Helper_Data $helper */
        $helper = Mage::helper('tinify/data');

        $tags = $helper->getAllowedTags();
        $tags = implode('|', $tags);
        $this->tagsPattern = str_replace('{tags}', $tags, $this->tagsPattern);

        $attributes = $helper->getAllowedAttributes();
        $attributes = implode('|', $attributes);
        $this->attributesPattern = str_replace('{attributes}', $attributes, $this->attributesPattern);
    }

    /**
     * Main HTML processing: replaces image links and adds missing ones to the conversion queue
     *
     * @param string $html
     *
     * @return string
     */
    public function processHtml(string $html): string
    {
        return preg_replace_callback($this->tagsPattern, function ($m) {
            $tag = $m[0];
            $modified = $tag;

            preg_match_all($this->attributesPattern, $tag, $attrMatches, PREG_SET_ORDER);
            foreach ($attrMatches as $attr) {
                [$attrHtml, $attrName, $attrQuote, $attrValue] = $attr;

                $isSrcset = stripos($attrName, 'srcset') !== false;
                $parts = $isSrcset ? preg_split('/\s*,\s*/', $attrValue) : [$attrValue];
                $newParts = [];

                foreach ($parts as $part) {
                    if (preg_match('/(?P<url>[^\s]+\.(jpe?g|png))(?!\.webp)(?P<rest>.*)/i', $part, $pm)) {
                        $url     = $pm['url'];
                        $fullUrl = $this->resolveFullUrl($url);
                        $rest    = $pm['rest'];

                        $path = parse_url($url, PHP_URL_PATH);
                        if (!$path || !$this->isInternalUrl($fullUrl)) {
                            $newParts[] = $part;
                            continue;
                        }

                        $fullPath = $this->getLocalFilePath($path);
                        $webpPath = $fullPath . '.webp';
                        $webpUrl  = $url . '.webp';

                        if ($this->fileExistsCached($webpPath)) {
                            $newParts[] = $webpUrl . $rest;
                        } else {
                            $this->batchPaths[$path] = $path;
                            $newParts[]              = $part;
                        }
                    } else {
                        $newParts[] = $part;
                    }
                }

                $newValue = implode($isSrcset ? ', ' : '', $newParts);
                $modified = str_replace($attrHtml, $attrName . '=' . $attrQuote . $newValue . $attrQuote, $modified);
            }

            return $modified;
        }, $html);
    }

    /**
     * Converts protocol-relative URL to full URL based on current scheme
     *
     * @param string $url
     *
     * @return string
     */
    protected function resolveFullUrl(string $url): string
    {
        return strpos($url, '//') === 0 ? $this->scheme . ':' . $url : $url;
    }

    /**
     * Checks whether a URL is internal (belongs to the current base URL)
     *
     * @param string $url
     *
     * @return bool
     */
    protected function isInternalUrl(string $url): bool
    {
        return strpos($url, 'http') !== 0 || strpos($url, $this->baseUrl) === 0;
    }

    /**
     * Resolves local filesystem path from relative URL path
     *
     * @param string $path
     *
     * @return string
     */
    protected function getLocalFilePath(string $path): string
    {
        return $this->baseDir . DS . ltrim($path, '/');
    }

    /**
     * Cached wrapper for file_exists to reduce repeated I/O
     *
     * @param string $path
     *
     * @return bool
     */
    protected function fileExistsCached(string $path): bool
    {
        if (!array_key_exists($path, $this->fileExistsCache)) {
            $this->fileExistsCache[$path] = file_exists($path);
        }
        return $this->fileExistsCache[$path];
    }

    /**
     * Flushes all collected image paths to the conversion queue
     *
     * @return void
     */
    public function flushBatch(): void
    {
        if (empty($this->batchPaths)) {
            return;
        }

        Mage::getModel('tinify/queue')->batchAddImages(array_values($this->batchPaths));
        $this->batchPaths = [];
    }

}
