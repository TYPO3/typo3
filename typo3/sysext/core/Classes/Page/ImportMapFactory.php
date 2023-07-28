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

namespace TYPO3\CMS\Core\Page;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\PolicyRegistry;
use TYPO3\CMS\Core\SingletonInterface;

#[Autoconfigure(public: true)]
readonly class ImportMapFactory implements SingletonInterface
{
    public function __construct(
        private HashService $hashService,
        private PackageManager $packageManager,
        private PolicyRegistry $policyRegistry,
        #[Autowire(service: 'cache.assets')]
        private FrontendInterface $assetsCache,
        private EventDispatcherInterface $eventDispatcher,
        #[Autowire(expression: 'service("package-dependent-cache-identifier").withPrefix("ImportMap").toString()')]
        private string $cacheIdentifier,
    ) {}

    public function create(bool $bustSuffix = true): ImportMap
    {
        $activePackages = array_values(
            $this->packageManager->getActivePackages()
        );
        return new ImportMap(
            $this->hashService,
            $activePackages,
            $this->policyRegistry,
            $this->assetsCache,
            $this->cacheIdentifier,
            $this->eventDispatcher,
            $bustSuffix
        );
    }
}
