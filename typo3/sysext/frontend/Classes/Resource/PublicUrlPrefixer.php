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

namespace TYPO3\CMS\Frontend\Resource;

use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class PublicUrlPrefixer
{
    /**
     * Static property to avoid an infinite loop, because this listener is called when
     * public URLs are generated, but also calls public URL generation to obtain the
     * URL without prefix from the driver and possibly other listeners
     *
     * @var bool
     */
    private static bool $isProcessingUrl = false;

    public function prefixWithAbsRefPrefix(GeneratePublicUrlForResourceEvent $event): void
    {
        $controller = $this->getCurrentFrontendController();
        if (self::$isProcessingUrl || !$controller) {
            return;
        }
        $resource = $event->getResource();
        if (!$this->isLocalResource($resource)) {
            return;
        }

        // Before calling getPublicUrl, we set the static property to true to avoid to be called in a loop
        self::$isProcessingUrl = true;
        try {
            $resource = $event->getResource();
            $originalUrl = $event->getStorage()->getPublicUrl($resource);
            if (!$originalUrl || PathUtility::hasProtocolAndScheme($originalUrl)) {
                return;
            }
            $event->setPublicUrl($controller->absRefPrefix . $originalUrl);
        } finally {
            self::$isProcessingUrl = false;
        }
    }

    private function isLocalResource(ResourceInterface $resource): bool
    {
        return $resource->getStorage()->getDriverType() === 'Local';
    }

    private function getCurrentFrontendController(): ?TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'] ?? null;
    }
}
