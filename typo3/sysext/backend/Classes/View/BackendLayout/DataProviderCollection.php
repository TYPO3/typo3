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

namespace TYPO3\CMS\Backend\View\BackendLayout;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Collection of backend layout data providers.
 */
class DataProviderCollection implements SingletonInterface
{
    /**
     * @var DataProviderInterface[]
     */
    protected array $dataProviders = [];
    protected array $results = [];

    /**
     * Adds a data provider to this collection.
     */
    public function add(string $identifier, string|object $classNameOrObject): void
    {
        if (str_contains($identifier, '__')) {
            throw new \UnexpectedValueException(
                'Identifier "' . $identifier . '" must not contain "__"',
                1381597629
            );
        }

        if (is_object($classNameOrObject)) {
            $className = get_class($classNameOrObject);
            $dataProvider = $classNameOrObject;
        } else {
            $className = $classNameOrObject;
            $dataProvider = GeneralUtility::makeInstance($classNameOrObject);
        }

        if (!$dataProvider instanceof DataProviderInterface) {
            throw new \LogicException(
                $className . ' must implement interface ' . DataProviderInterface::class,
                1381269811
            );
        }

        $this->dataProviders[$identifier] = $dataProvider;
    }

    /**
     * Gets all backend layout collections and thus, all
     * backend layouts. Each data provider returns its own
     * backend layout collection.
     *
     * @return BackendLayoutCollection[]
     */
    public function getBackendLayoutCollections(DataProviderContext $dataProviderContext): array
    {
        $result = [];

        foreach ($this->dataProviders as $identifier => $dataProvider) {
            $backendLayoutCollection = $this->createBackendLayoutCollection($identifier);
            $dataProvider->addBackendLayouts($dataProviderContext, $backendLayoutCollection);
            $result[$identifier] = $backendLayoutCollection;
        }

        return $result;
    }

    /**
     * Gets a backend layout by a combined identifier, which is
     * e.g. "myextension_regular" and "myextension" is the identifier
     * of the accordant data provider and "regular" the identifier of
     * the accordant backend layout.
     */
    public function getBackendLayout(string $combinedIdentifier, int $pageId): ?BackendLayout
    {
        $backendLayout = null;

        if (!str_contains($combinedIdentifier, '__')) {
            $dataProviderIdentifier = 'default';
            $backendLayoutIdentifier = $combinedIdentifier;
        } else {
            [$dataProviderIdentifier, $backendLayoutIdentifier] = explode('__', $combinedIdentifier, 2);
        }

        if (isset($this->dataProviders[$dataProviderIdentifier])) {
            $backendLayout = $this->dataProviders[$dataProviderIdentifier]->getBackendLayout($backendLayoutIdentifier, $pageId);
        }

        return $backendLayout;
    }

    /**
     * Creates a new backend layout collection.
     */
    protected function createBackendLayoutCollection(string $identifier): BackendLayoutCollection
    {
        return GeneralUtility::makeInstance(
            BackendLayoutCollection::class,
            $identifier
        );
    }
}
