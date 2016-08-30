<?php
namespace TYPO3\CMS\Core\Resource\OnlineMedia\Helpers;

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

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class AbstractOEmbedHelper
 * See http://oembed.com/ for more on OEmbed specification
 */
abstract class AbstractOEmbedHelper extends AbstractOnlineMediaHelper
{
    /**
     * @param string $mediaId
     * @param string $format
     * @return string
     */
    abstract protected function getOEmbedUrl($mediaId, $format = 'json');

    /**
     * Transform mediaId to File
     *
     * @param string $mediaId
     * @param Folder $targetFolder
     * @param string $fileExtension
     * @return File
     */
    protected function transformMediaIdToFile($mediaId, Folder $targetFolder, $fileExtension)
    {
        $file = $this->findExistingFileByOnlineMediaId($mediaId, $targetFolder, $fileExtension);

        // no existing file create new
        if ($file === null) {
            $oEmbed = $this->getOEmbedData($mediaId);
            if (!empty($oEmbed)) {
                $fileName = $oEmbed['title'] . '.' . $fileExtension;
            } else {
                $fileName = $mediaId . '.' . $fileExtension;
            }
            $file = $this->createNewFile($targetFolder, $fileName, $mediaId);
        }
        return $file;
    }

    /**
     * Get OEmbed data
     *
     * @param string $mediaId
     * @return array|NULL
     */
    protected function getOEmbedData($mediaId)
    {
        $oEmbed = GeneralUtility::getUrl(
            $this->getOEmbedUrl($mediaId)
        );
        if ($oEmbed) {
            $oEmbed = json_decode($oEmbed, true);
        }
        return $oEmbed;
    }

    /**
     * Get meta data for OnlineMedia item
     * Using the meta data from oEmbed
     *
     * @param File $file
     * @return array with metadata
     */
    public function getMetaData(File $file)
    {
        $metadata = [];

        $oEmbed = $this->getOEmbedData($this->getOnlineMediaId($file));

        if ($oEmbed) {
            $metadata['width'] = (int)$oEmbed['width'];
            $metadata['height'] = (int)$oEmbed['height'];
            if (empty($file->getProperty('title'))) {
                $metadata['title'] = strip_tags($oEmbed['title']);
            }
            $metadata['author'] = $oEmbed['author_name'];
        }

        return $metadata;
    }
}
