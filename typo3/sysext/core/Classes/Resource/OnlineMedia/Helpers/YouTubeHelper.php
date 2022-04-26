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
 * Youtube helper class
 */
class YouTubeHelper extends AbstractOEmbedHelper
{
    /**
     * Get public url
     *
     * @param File $file
     * @param bool $relativeToCurrentScript Deprecated since TYPO3 v11, will be removed in TYPO3 v12.0
     * @return string|null
     */
    public function getPublicUrl(File $file, $relativeToCurrentScript = false)
    {
        // @deprecated $relativeToCurrentScript since v11, will be removed in TYPO3 v12.0
        $videoId = $this->getOnlineMediaId($file);
        return sprintf('https://www.youtube.com/watch?v=%s', rawurlencode($videoId));
    }

    /**
     * Get local absolute file path to preview image
     *
     * @param File $file
     * @return string
     */
    public function getPreviewImage(File $file)
    {
        $videoId = $this->getOnlineMediaId($file);
        $temporaryFileName = $this->getTempFolderPath() . 'youtube_' . md5($videoId) . '.jpg';

        if (!file_exists($temporaryFileName)) {
            $tryNames = ['maxresdefault.jpg', 'mqdefault.jpg', '0.jpg'];
            foreach ($tryNames as $tryName) {
                $previewImage = GeneralUtility::getUrl(
                    sprintf('https://img.youtube.com/vi/%s/%s', $videoId, $tryName)
                );
                if ($previewImage !== false) {
                    file_put_contents($temporaryFileName, $previewImage);
                    GeneralUtility::fixPermissions($temporaryFileName);
                    break;
                }
            }
        }

        return $temporaryFileName;
    }

    /**
     * Try to transform given URL to a File
     *
     * @param string $url
     * @param Folder $targetFolder
     * @return File|null
     */
    public function transformUrlToFile($url, Folder $targetFolder)
    {
        $videoId = null;
        // Try to get the YouTube code from given url.
        // These formats are supported with and without http(s)://
        // - youtu.be/<code> # Share URL
        // - www.youtube.com/watch?v=<code> # Normal web link
        // - www.youtube.com/v/<code>
        // - www.youtube-nocookie.com/v/<code> # youtube-nocookie.com web link
        // - www.youtube.com/embed/<code> # URL form iframe embed code, can also get code from full iframe snippet
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match)) {
            $videoId = $match[1];
        }
        if (empty($videoId)) {
            return null;
        }
        return $this->transformMediaIdToFile($videoId, $targetFolder, $this->extension);
    }

    /**
     * Get oEmbed url to retrieve oEmbed data
     *
     * @param string $mediaId
     * @param string $format
     * @return string
     */
    protected function getOEmbedUrl($mediaId, $format = 'json')
    {
        return sprintf(
            'https://www.youtube.com/oembed?url=%s&format=%s&maxwidth=2048&maxheight=2048',
            rawurlencode(sprintf('https://www.youtube.com/watch?v=%s', rawurlencode($mediaId))),
            rawurlencode($format)
        );
    }
}
