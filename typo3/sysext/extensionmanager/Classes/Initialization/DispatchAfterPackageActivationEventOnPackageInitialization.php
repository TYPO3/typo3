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

namespace TYPO3\CMS\Extensionmanager\Initialization;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Package\Event\AfterPackageActivationEvent;
use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Dispatches the DispatchAfterPackageActivationEvent on package initialization.
 */
final readonly class DispatchAfterPackageActivationEventOnPackageInitialization
{
    #[AsEventListener]
    public function __invoke(PackageInitializationEvent $event): void
    {
        // Only dispatch event in case package activation has been triggered via InstallUtility
        if (($container = $event->getContainer()) === null
            || !(($emitter = $event->getEmitter()) instanceof InstallUtility)
        ) {
            return;
        }

        $container->get(EventDispatcherInterface::class)->dispatch(
            new AfterPackageActivationEvent(
                $event->getExtensionKey(),
                'typo3-cms-extension',
                $emitter
            )
        );
    }
}
