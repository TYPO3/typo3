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

namespace TYPO3\CMS\Extbase\Persistence\Generic;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Configuration\Exception\NoServerRequestGivenException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * The QueryFactory used to create queries against the storage backend
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
readonly class QueryFactory implements QueryFactoryInterface
{
    public function __construct(
        protected ConfigurationManagerInterface $configurationManager,
        protected DataMapFactory $dataMapFactory,
    ) {}

    /**
     * Creates a query object working on the given class name
     *
     * @param string $className The class name
     * @template T of object
     * @phpstan-param class-string<T> $className
     * @phpstan-return QueryInterface<T>
     */
    public function create($className): QueryInterface
    {
        $query = GeneralUtility::makeInstance(QueryInterface::class);
        $query->setType($className);
        $querySettings = GeneralUtility::makeInstance(QuerySettingsInterface::class);

        $dataMap = $this->dataMapFactory->buildDataMap($className);
        if ($dataMap->isStatic || $dataMap->rootLevel) {
            $querySettings->setRespectStoragePage(false);
        }

        $storagePid = '0';
        try {
            $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
            $storagePid = (string)($frameworkConfiguration['persistence']['storagePid'] ?? '0');
        } catch (NoServerRequestGivenException) {
            // Fallback to storagePid 0 if ConfigurationManager has not been initialized with a Request. This
            // is a measure to specifically allow running the extbase persistence layer without a Request, which
            // may be useful in some CLI scenarios (and can be convenient in tests) when no other code branches
            // of extbase that have a hard dependency to the Request (e.g. controllers / view) are used.
        }

        $querySettings->setStoragePageIds(GeneralUtility::intExplode(',', $storagePid));
        $query->setQuerySettings($querySettings);
        return $query;
    }
}
