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

namespace TYPO3\CMS\Adminpanel\Service;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\AfterFileProcessingEvent;

/**
 * Collects the images processed during the current frontend request, so the
 * "Info" admin panel module can list them. The service is shared, so all
 * processing calls of a request accumulate in the same instance.
 *
 * @internal
 */
final class ProcessedImageCollector
{
    /**
     * @var array<string, array{name: string, size: int, width: int, height: int}>
     */
    private array $images = [];

    #[AsEventListener('typo3/cms-adminpanel/collect-processed-images')]
    public function collect(AfterFileProcessingEvent $event): void
    {
        if (!$this->isCollecting()) {
            return;
        }
        $processedFile = $event->getProcessedFile();
        $publicUrl = $processedFile->getPublicUrl();
        if ($publicUrl === null) {
            return;
        }
        try {
            $size = $processedFile->getSize();
        } catch (\RuntimeException) {
            // A missing or deleted file must not break rendering of the admin panel
            $size = 0;
        }
        $this->images[$publicUrl] = [
            'name' => $publicUrl,
            'size' => $size,
            'width' => (int)$processedFile->getProperty('width'),
            'height' => (int)$processedFile->getProperty('height'),
        ];
    }

    /**
     * @return array<string, array{name: string, size: int, width: int, height: int}>
     */
    public function getImages(): array
    {
        return $this->images;
    }

    private function isCollecting(): bool
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        // Files are also processed outside a TYPO3 application request, for instance in CLI
        // commands. ApplicationType::fromRequest() throws for those, so check the attribute first.
        if (!$request instanceof ServerRequestInterface
            || !is_int($request->getAttribute('applicationType'))
            || !ApplicationType::fromRequest($request)->isFrontend()
        ) {
            return false;
        }
        return StateUtility::isActivatedForUser() && StateUtility::isOpen();
    }
}
