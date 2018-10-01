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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class FileMetadataTranslationAspect
 *
 * We do not have AOP in TYPO3 for now, thus the aspect which
 * deals with metadata translation is a slot which reacts on a signal
 * in the Index\MetadataRepository.
 *
 * The aspect injects user permissions and mount points into the storage
 * based on user or group configuration.
 *
 * @internal this is a concrete TYPO3 hook implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
class FileMetadataOverlayAspect
{
    /**
     * Do translation and workspace overlay
     *
     * @param \ArrayObject $data
     */
    public function languageAndWorkspaceOverlay(\ArrayObject $data)
    {
        // Should only be in Frontend, but not in eID context
        if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_FE) || isset($_REQUEST['eID'])) {
            return;
        }
        $overlaidMetaData = $data->getArrayCopy();
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $pageRepository->versionOL('sys_file_metadata', $overlaidMetaData);
        $overlaidMetaData = $pageRepository->getLanguageOverlay(
            'sys_file_metadata',
            $overlaidMetaData
        );
        if ($overlaidMetaData !== null) {
            $data->exchangeArray($overlaidMetaData);
        }
    }
}
