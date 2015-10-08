<?php
namespace TYPO3\CMS\Frontend\Aspect;

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

/**
 * Class FileMetadataTranslationAspect
 *
 * We do not have AOP in TYPO3 for now, thus the aspect which
 * deals with metadata translation is a slot which reacts on a signal
 * in the Index\MetadataRepository.
 *
 * The aspect injects user permissions and mount points into the storage
 * based on user or group configuration.
 */
class FileMetadataOverlayAspect
{
    /**
     * Do translation and workspace overlay
     *
     * @param \ArrayObject $data
     * @return void
     */
    public function languageAndWorkspaceOverlay(\ArrayObject $data)
    {
        $overlaidMetaData = $data->getArrayCopy();
        $this->getTsfe()->sys_page->versionOL('sys_file_metadata', $overlaidMetaData);
        $overlaidMetaData = $this->getTsfe()->sys_page->getRecordOverlay(
            'sys_file_metadata',
            $overlaidMetaData,
            $this->getTsfe()->sys_language_content,
            $this->getTsfe()->sys_language_contentOL
        );
        if ($overlaidMetaData !== null) {
            $data->exchangeArray($overlaidMetaData);
        }
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTsfe()
    {
        return $GLOBALS['TSFE'];
    }
}
