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

namespace TYPO3\CMS\Install\Factory;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Page\Event\ResolveVirtualJavaScriptImportEvent;
use TYPO3\CMS\Core\Page\ImportMap;

final class ImportMapFactory
{
    public function __construct(
        private readonly FailsafePackageManager $packageManager,
        private readonly HashService $hashService,
    ) {}

    public function create(string $sitePath): ImportMap
    {
        $packages = [
            $this->packageManager->getPackage('core'),
            $this->packageManager->getPackage('backend'),
            $this->packageManager->getPackage('install'),
        ];
        $bust = (string)$GLOBALS['EXEC_TIME'];
        if (!Environment::getContext()->isDevelopment()) {
            $bust = $this->hashService->hmac((new Typo3Version()) . Environment::getProjectPath(), self::class);
        }
        return new ImportMap(
            hashService: $this->hashService,
            packages: $packages,
            eventDispatcher: $this->createEventDispatcher($sitePath, $bust),
        );
    }

    public function createEventDispatcher(string $sitePath, string $bust): EventDispatcherInterface
    {
        return new EventDispatcher(
            new class ($sitePath, $bust) implements ListenerProviderInterface {
                public function __construct(private string $sitePath, private string $bust) {}
                public function getListenersForEvent(object $event): iterable
                {
                    if ($event instanceof ResolveVirtualJavaScriptImportEvent) {
                        return [
                            function (ResolveVirtualJavaScriptImportEvent $event): void {
                                if ($event->resolution === null && str_starts_with($event->virtualName, 'install-labels/')) {
                                    $parameters = [
                                        'install' => [
                                            'action' => 'labels',
                                            'domain' => str_replace('install-labels/', '', $event->virtualName),
                                            'bust' => $this->bust,
                                        ],
                                    ];
                                    $event->resolution = '/' . ltrim($this->sitePath, '/') . '?__typo3_install&'
                                        . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
                                }
                            },
                        ];
                    }
                    return [];
                }
            }
        );
    }
}
