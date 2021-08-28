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

namespace TYPO3\CMS\Extbase\Configuration;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface;

/**
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final class RequestHandlersConfigurationFactory
{
    private FrontendInterface $cache;

    private PackageManager $packageManager;

    private string $cacheIdentifier;

    public function __construct(FrontendInterface $cache, PackageManager $packageManager, string $cacheIdentifier)
    {
        $this->cache = $cache;
        $this->packageManager = $packageManager;
        $this->cacheIdentifier = $cacheIdentifier;
    }

    /**
     * @return RequestHandlersConfiguration
     * @throws Exception
     */
    public function createRequestHandlersConfiguration(): RequestHandlersConfiguration
    {
        $requestHandlersCache = $this->cache->get($this->cacheIdentifier);
        if ($requestHandlersCache) {
            return new RequestHandlersConfiguration($requestHandlersCache);
        }

        $classes = [];
        foreach ($this->packageManager->getActivePackages() as $activePackage) {
            $requestHandlersFile = $activePackage->getPackagePath() . 'Configuration/Extbase/RequestHandlers.php';
            if (file_exists($requestHandlersFile)) {
                $definedClasses = require $requestHandlersFile;
                if (!is_array($definedClasses)) {
                    continue;
                }

                foreach ($definedClasses as $definedClass) {
                    if (!class_exists($definedClass)) {
                        throw new Exception(
                            sprintf(
                                'Request class "%s", registered in "%s", does not exist.',
                                $definedClass,
                                $requestHandlersFile
                            ),
                            1562253559
                        );
                    }

                    if (!in_array(RequestHandlerInterface::class, class_implements($definedClass), true)) {
                        throw new Exception(
                            sprintf(
                                'Request class "%s", registered in "%s", does not implement interface "%s".',
                                $definedClass,
                                $requestHandlersFile,
                                RequestHandlerInterface::class
                            ),
                            1562257073
                        );
                    }

                    $classes[] = $definedClass;
                }
            }
        }

        $this->cache->set($this->cacheIdentifier, $classes);

        return new RequestHandlersConfiguration($classes);
    }
}
