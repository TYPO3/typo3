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

namespace TYPO3\CMS\Backend\Resource;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\PathUtility;

#[Autoconfigure(public: true)]
class PublicUrlPrefixer
{
    /**
     * Static property to avoid an infinite loop, because this listener is called when
     * public URLs are generated, but also calls public URL generation to obtain the
     * URL without prefix from the driver and possibly other listeners
     */
    private static bool $isProcessingUrl = false;

    public function prefixWithSitePath(GeneratePublicUrlForResourceEvent $event): void
    {
        $normalizedParams = ($GLOBALS['TYPO3_REQUEST'] ?? null)?->getAttribute('normalizedParams');
        if (self::$isProcessingUrl || !$normalizedParams) {
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
            // @todo: The "dynamic" event registration in both FE and BE RequestHandler's plus this
            //        ugly late Request dependency resolving within FAL should be refactored by
            //        incorporating Request dependency into FAL URI generation in a more direct way.
            $event->setPublicUrl($normalizedParams->getSitePath() . $originalUrl);
        } finally {
            self::$isProcessingUrl = false;
        }
    }

    private function isLocalResource(ResourceInterface $resource): bool
    {
        return $resource->getStorage()->getDriverType() === 'Local';
    }
}
