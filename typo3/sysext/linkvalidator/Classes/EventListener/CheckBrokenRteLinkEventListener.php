<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Linkvalidator\EventListener;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Html\Event\BrokenLinkAnalysisEvent;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;

/**
 * Event listeners to identify if a link is broken. For external URLs, the linkvalidator
 * is used (not in real-time but from the database), for pages this is handled via a check to the database
 * record.
 */
final class CheckBrokenRteLinkEventListener
{
    /**
     * @var BrokenLinkRepository
     */
    private $brokenLinkRepository;

    public function __construct(BrokenLinkRepository $brokenLinkRepository)
    {
        $this->brokenLinkRepository = $brokenLinkRepository;
    }

    public function checkExternalLink(BrokenLinkAnalysisEvent $event): void
    {
        if ($event->getLinkType() !== LinkService::TYPE_URL) {
            return;
        }
        $url = (string)$event->getLinkData()['url'] ?? '';
        if (!empty($url)) {
            if ($this->brokenLinkRepository->isLinkTargetBrokenLink($url)) {
                $event->markAsBrokenLink('External link is broken');
            }
        }
        $event->markAsCheckedLink();
    }

    public function checkPageLink(BrokenLinkAnalysisEvent $event): void
    {
        if ($event->getLinkType() !== LinkService::TYPE_PAGE) {
            return;
        }
        $hrefInformation = $event->getLinkData();
        if ($hrefInformation['pageuid'] !== 'current') {
            $pageRecord = BackendUtility::getRecord('pages', $hrefInformation['pageuid']);
            // Page does not exist
            if (!is_array($pageRecord)) {
                $event->markAsBrokenLink('Page with ID ' . htmlspecialchars($hrefInformation['pageuid']) . ' not found');
            }
        }
        $event->markAsCheckedLink();
    }

    public function checkFileLink(BrokenLinkAnalysisEvent $event): void
    {
        if ($event->getLinkType() !== LinkService::TYPE_FILE) {
            return;
        }
        $event->markAsCheckedLink();

        $hrefInformation = $event->getLinkData();
        $file = $hrefInformation['file'] ?? null;
        if (!$file instanceof FileInterface) {
            $event->markAsBrokenLink('File link is broken');
            return;
        }

        if (!$file->hasProperty('uid') || (int)$file->getProperty('uid') === 0) {
            $event->markAsBrokenLink('File link is broken');
            return;
        }

        if ($this->brokenLinkRepository->isLinkTargetBrokenLink('file:' . $file->getProperty('uid'))) {
            $event->markAsBrokenLink('File with ID ' . $file->getProperty('uid') . ' not found');
        }
    }
}
