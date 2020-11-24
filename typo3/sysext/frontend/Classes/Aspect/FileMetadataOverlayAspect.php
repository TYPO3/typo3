<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Frontend\Aspect;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\EnrichFileMetaDataEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class deals with metadata translation as a event listener which reacts on an event MetadataRepository.
 *
 * The listener injects user permissions and mount points into the storage
 * based on user or group configuration.
 *
 * @internal this is a concrete TYPO3 Event Listener and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
final class FileMetadataOverlayAspect
{
    /**
     * Do translation and workspace overlay
     * @param EnrichFileMetaDataEvent $event
     */
    public function languageAndWorkspaceOverlay(EnrichFileMetaDataEvent $event): void
    {
        // Should only be in Frontend, but not in eID context
        if (!($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            || !ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
            || isset($_REQUEST['eID'])
        ) {
            return;
        }
        $overlaidMetaData = $event->getRecord();
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        $pageRepository->versionOL('sys_file_metadata', $overlaidMetaData);
        $overlaidMetaData = $pageRepository
            ->getLanguageOverlay(
                'sys_file_metadata',
                $overlaidMetaData
            );
        if ($overlaidMetaData !== null) {
            $event->setRecord($overlaidMetaData);
        }
    }
}
