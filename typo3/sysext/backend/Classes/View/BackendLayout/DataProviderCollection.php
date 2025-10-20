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

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Collection of backend layout data providers.
 */
class DataProviderCollection implements SingletonInterface
{
    /**
     * @var array<non-empty-string, DataProviderInterface>
     */
    protected array $dataProviders = [];
    protected array $results = [];

    /**
     * @param iterable<DataProviderInterface> $dataProviders
     */
    public function __construct(
        #[AutowireIterator('page_layout.data_provider')]
        iterable $dataProviders = [],
    ) {
        foreach ($dataProviders as $dataProvider) {
            $this->validateDataProvider($dataProvider);
            $identifier = $dataProvider->getIdentifier();

            if (isset($this->dataProviders[$identifier])) {
                throw new \LogicException(
                    sprintf(
                        'A backend layout data provider with identifier "%s" is already registered.',
                        $identifier
                    ),
                    1762361129,
                );
            }

            $this->dataProviders[$identifier] = $dataProvider;
        }
    }

    protected function validateDataProvider(mixed $dataProvider): void
    {
        if (!($dataProvider instanceof DataProviderInterface)) {
            throw new \LogicException(
                sprintf(
                    'Data provider must implement interface %s, %s given.',
                    DataProviderInterface::class,
                    \get_debug_type($dataProvider),
                ),
                1381269811,
            );
        }

        $identifier = $dataProvider->getIdentifier();

        if (str_contains($identifier, '__')) {
            throw new \UnexpectedValueException('Identifier "' . $identifier . '" must not contain "__"', 1381597629);
        }
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
