<?php

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

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ForwardCompatibleQueryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * The QueryFactory used to create queries against the storage backend
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class QueryFactory implements QueryFactoryInterface, SingletonInterface
{
    protected ConfigurationManagerInterface $configurationManager;
    protected DataMapFactory $dataMapFactory;
    private ContainerInterface $container;

    public function __construct(
        ConfigurationManagerInterface $configurationManager,
        DataMapFactory $dataMapFactory,
        ContainerInterface $container
    ) {
        $this->configurationManager = $configurationManager;
        $this->dataMapFactory = $dataMapFactory;
        $this->container = $container;
    }

    /**
     * Creates a query object working on the given class name
     *
     * @param string $className The class name
     * @return QueryInterface
     */
    public function create($className): QueryInterface
    {
        $query = $this->container->get(QueryInterface::class);
        if ($query instanceof ForwardCompatibleQueryInterface) {
            $query->setType($className);
        } else {
            // @deprecated since v11, will be removed in v12. Use ObjectManager if an implementation does not implement ForwardCompatibleQueryInterface.
            $query = GeneralUtility::makeInstance(ObjectManager::class)->get(QueryInterface::class, $className);
        }

        $querySettings = GeneralUtility::makeInstance(QuerySettingsInterface::class);

        $dataMap = $this->dataMapFactory->buildDataMap($className);
        if ($dataMap->getIsStatic() || $dataMap->getRootLevel()) {
            $querySettings->setRespectStoragePage(false);
        }

        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $querySettings->setStoragePageIds(GeneralUtility::intExplode(',', $frameworkConfiguration['persistence']['storagePid'] ?? ''));
        $query->setQuerySettings($querySettings);
        return $query;
    }
}
