<?php
namespace Snaptec\Brand\Api;

interface BrandInterface
{
    /**
     * @param string $title
     * @param string $content
     * @param string $image
     * @return bool
     */
    public function saveBrand($id = null, $title, $content);
}
