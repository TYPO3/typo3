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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface;

/**
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final class RequestHandlersConfigurationFactory implements SingletonInterface
{
    /**
     * @var FrontendInterface
     */
    private $cacheFrontend;

    /**
     * @param CacheManager|null $cacheManager can be null to disable caching in this factory
     */
    public function __construct(CacheManager $cacheManager = null)
    {
        $cacheIdentifier = 'extbase';

        $cacheFrontend = new NullFrontend($cacheIdentifier);
        if ($cacheManager !== null) {
            try {
                $cacheFrontend = $cacheManager->getCache($cacheIdentifier);
            } catch (NoSuchCacheException $e) {
                // Handling this exception is not needed as $cacheFrontend is
                // a NullFrontend at this moment.
            }
        }

        $this->cacheFrontend = $cacheFrontend;
    }

    /**
     * @return RequestHandlersConfiguration
     * @throws Exception
     */
    public function createRequestHandlersConfiguration(): RequestHandlersConfiguration
    {
        $cacheEntryIdentifier = 'RequestHandlers_' . sha1((string)(new Typo3Version()) . Environment::getProjectPath());

        $requestHandlersCache = $this->cacheFrontend->get($cacheEntryIdentifier);
        if ($requestHandlersCache) {
            return new RequestHandlersConfiguration($requestHandlersCache);
        }

        $classes = [];
        foreach (GeneralUtility::makeInstance(PackageManager::class)->getActivePackages() as $activePackage) {
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

        $this->cacheFrontend->set($cacheEntryIdentifier, $classes);

        return new RequestHandlersConfiguration($classes);
    }
}
