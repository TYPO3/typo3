<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Resource\OnlineMedia\Helpers;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Vimeo helper class
 */
class VimeoHelper extends AbstractOEmbedHelper
{
    /**
     * Get public url
     * Return NULL if you want to use core default behaviour
     *
     * @return string|null
     */
    public function getPublicUrl(File $file)
    {
        $videoId = $this->getOnlineMediaId($file);
        return sprintf('https://vimeo.com/%s', rawurlencode($videoId));
    }

    /**
     * Get local absolute file path to preview image
     *
     * @return string
     */
    public function getPreviewImage(File $file)
    {
        $videoId = $this->getOnlineMediaId($file);
        $temporaryFileName = $this->getTempFolderPath() . 'vimeo_' . md5($videoId) . '.jpg';
        if (!file_exists($temporaryFileName)) {
            $oEmbedData = $this->getOEmbedData($videoId);
            if (!empty($oEmbedData['thumbnail_url'])) {
                $previewImage = GeneralUtility::getUrl($oEmbedData['thumbnail_url']);
                if ($previewImage !== false) {
                    GeneralUtility::writeFile($temporaryFileName, $previewImage, true);
                }
            }
        }
        return $temporaryFileName;
    }

    /**
     * Try to transform given URL to a File
     *
     * @param string $url
     * @return File|null
     */
    public function transformUrlToFile($url, Folder $targetFolder)
    {
        $videoId = null;
        // Try to get the Vimeo code from given url.
        // Next formats are supported with and without http(s)://
        // - vimeo.com/<code>/<optionalPrivateCode> # Share URL
        // - vimeo.com/event/<code>
        // - player.vimeo.com/video/<code>/<optionalPrivateCode> # URL form iframe embed code, can also get code from full iframe snippet
        if (preg_match('/vimeo\.com\/(?:video\/|event\/)?([0-9a-z\/]+)/i', $url, $matches)) {
            $videoId = $matches[1];
        }
        if (empty($videoId)) {
            return null;
        }
        return $this->transformMediaIdToFile($videoId, $targetFolder, $this->extension);
    }

    /**
     * Get oEmbed data url
     *
     * @param string $mediaId
     * @param string $format
     * @return string
     */
    protected function getOEmbedUrl($mediaId, $format = 'json')
    {
        return sprintf(
            'https://vimeo.com/api/oembed.%s?width=2048&url=%s',
            rawurlencode($format),
            rawurlencode(sprintf('https://vimeo.com/%s', rawurlencode($mediaId)))
        );
    }
}
