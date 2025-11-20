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

namespace TYPO3\CMS\Core\Package\Initialization;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;

/**
 * Listener to publish assets after package activation
 */
final readonly class PublishAssetsOnPackageInitialization
{
    public function __construct(
        private SystemResourcePublisherInterface $publisher,
    ) {}

    #[AsEventListener]
    public function __invoke(PackageInitializationEvent $event): void
    {
        $this->publisher->publishResources($event->getPackage());
    }
}
