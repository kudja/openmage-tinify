<?php

class Kudja_Tinify_Model_Response_Processor
{
    /** @var Kudja_Tinify_Helper_Data */
    protected $helper;

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
        $this->helper = Mage::helper('tinify/data');

        $tags = $this->helper->getAllowedTags();
        $tags = implode('|', $tags);
        $this->tagsPattern = str_replace('{tags}', $tags, $this->tagsPattern);

        $attributes = $this->helper->getAllowedAttributes();
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
                        $fullUrl = $this->helper->resolveFullUrl($url);
                        $rest    = $pm['rest'];

                        $path = parse_url($url, PHP_URL_PATH);
                        if (!$path || !$this->helper->isInternalUrl($fullUrl)) {
                            $newParts[] = $part;
                            continue;
                        }

                        $fullPath = $this->helper->getLocalFilePath($path);
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
     * @param string $json
     *
     * @return string
     */
    public function processJson(string $json): string
    {
        $result = $json;
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            $data = $this->processArray($data);

            $result = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            Mage::log('JSON processing error: ' . $e->getMessage(), Zend_Log::ERR, 'tinify.log');
        } catch (Exception $e) {
            Mage::log('General error: ' . $e->getMessage(), Zend_Log::ERR, 'tinify.log');
        }

        return $result;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function processArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->processArray($value);
            } elseif (is_string($value)) {
                if (preg_match('/<[^>]+>/', $value) === 1) {
                    $data[$key] = $this->processHtml($value);
                    continue;
                }

                if (preg_match('~\.(jpe?g|png)$~i', $value)) {
                    $url = $value;
                    $path = parse_url($url, PHP_URL_PATH);
                    if (!$path || !$this->helper->isInternalUrl($url)) {
                        continue;
                    }

                    $fullPath = $this->helper->getLocalFilePath($path);
                    $webpPath = $fullPath . '.webp';
                    $webpUrl  = $url . '.webp';

                    if ($this->fileExistsCached($webpPath)) {
                        $data[$key] = $webpUrl;
                        continue;
                    }

                    $this->batchPaths[$path] = $path;
                }
            }
        }

        return $data;
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

        Mage::getSingleton('tinify/service_queue')->batchAddImages(array_values($this->batchPaths));
        $this->batchPaths = [];
    }

}
