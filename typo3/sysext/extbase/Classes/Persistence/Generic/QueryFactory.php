<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

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

/**
 * The QueryFactory used to create queries against the storage backend
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class QueryFactory implements QueryFactoryInterface, \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
     */
    protected $dataMapFactory;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory $dataMapFactory
     */
    public function injectDataMapFactory(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory $dataMapFactory)
    {
        $this->dataMapFactory = $dataMapFactory;
    }

    /**
     * Creates a query object working on the given class name
     *
     * @param string $className The class name
     * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
     * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception
     */
    public function create($className)
    {
        $query = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class, $className);
        $querySettings = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);

        $dataMap = $this->dataMapFactory->buildDataMap($className);
        if ($dataMap->getIsStatic() || $dataMap->getRootLevel()) {
            $querySettings->setRespectStoragePage(false);
        }

        $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $querySettings->setStoragePageIds(\TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $frameworkConfiguration['persistence']['storagePid'] ?? ''));
        $query->setQuerySettings($querySettings);
        return $query;
    }
}
