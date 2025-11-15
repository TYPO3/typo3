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

namespace TYPO3\CMS\Form\Mvc\Persistence;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;
use TYPO3\CMS\Form\Mvc\Persistence\Event\AfterFormDefinitionLoadedEvent;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniqueIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniquePersistenceIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Storage\StorageAdapterFactory;

/**
 * Form Persistence Manager - Entry point for FormManagerController
 *
 * This manager acts as a facade that delegates storage operations to appropriate
 * storage adapters via the StorageAdapterFactory.
 *
 * Scope: frontend / backend
 * @internal
 */
#[AsAlias(FormPersistenceManagerInterface::class)]
readonly class FormPersistenceManager implements FormPersistenceManagerInterface
{
    public function __construct(
        private StorageAdapterFactory $storageAdapterFactory,
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
        private EventDispatcherInterface $eventDispatcher,
        private TypoScriptService $typoScriptService,
    ) {}

    /**
     * Load form definition and apply event listeners and TypoScript overrides
     */
    public function load(string $persistenceIdentifier, ?array $typoScriptSettings = null, ?ServerRequestInterface $request = null): array
    {
        $cacheKey = 'ext-form-load-' . hash('xxh3', $persistenceIdentifier);

        if ($this->runtimeCache->has($cacheKey)) {
            $formDefinition = $this->runtimeCache->get($cacheKey);
        } else {
            $formDefinition = $this->loadFromStorage($persistenceIdentifier);
            $this->runtimeCache->set($cacheKey, $formDefinition);
        }

        $formDefinition = $this->eventDispatcher
            ->dispatch(new AfterFormDefinitionLoadedEvent($formDefinition, $persistenceIdentifier, $cacheKey))
            ->getFormDefinition();

        if ($request !== null && !empty($typoScriptSettings['formDefinitionOverrides'][$formDefinition['identifier']] ?? null)) {
            $formDefinitionOverrides = $this->typoScriptService->resolvePossibleTypoScriptConfiguration(
                $typoScriptSettings['formDefinitionOverrides'][$formDefinition['identifier']],
                $request
            );
            ArrayUtility::mergeRecursiveWithOverrule($formDefinition, $formDefinitionOverrides);
        }

        return $formDefinition;
    }

    /**
     * Save form definition to appropriate storage
     */
    public function save(string $persistenceIdentifier, array $formDefinition, array $formSettings): void
    {
        if (!$this->isAllowedPersistencePath($persistenceIdentifier, $formSettings)) {
            throw new PersistenceManagerException(
                sprintf('Save to path "%s" is not allowed.', $persistenceIdentifier),
                1477680881
            );
        }

        $identifier = new FormIdentifier($persistenceIdentifier);
        $adapter = $this->storageAdapterFactory->getAdapterForIdentifier($persistenceIdentifier);
        $formData = FormData::fromArray($formDefinition);

        $adapter->write($identifier, $formData);

        $this->clearFormCache($persistenceIdentifier);
    }

    /**
     * Delete form definition from storage
     */
    public function delete(string $persistenceIdentifier, array $formSettings): void
    {
        $identifier = new FormIdentifier($persistenceIdentifier);
        $adapter = $this->storageAdapterFactory->getAdapterForIdentifier($persistenceIdentifier);

        if (!$adapter->exists($identifier)) {
            throw new PersistenceManagerException(
                sprintf('The form "%s" does not exist.', $persistenceIdentifier),
                1472239535
            );
        }

        $adapter->delete($identifier);
        $this->clearFormCache($persistenceIdentifier);
    }

    /**
     * List all form definitions from all available storages
     */
    public function listForms(array $formSettings, SearchCriteria $searchCriteria): array
    {
        $identifiers = [];
        $forms = [];

        foreach ($this->storageAdapterFactory->getAllAdapters() as $adapter) {
            try {
                $formDataList = $adapter->findAll($searchCriteria);

                foreach ($formDataList as $formData) {
                    if ($formData->storageType === null) {
                        $formData = $formData->withStorageType($adapter->getTypeIdentifier());
                    }
                    $forms[] = $formData;
                    if (!isset($identifiers[$formData->identifier])) {
                        $identifiers[$formData->identifier] = 0;
                    }
                    $identifiers[$formData->identifier]++;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        foreach ($identifiers as $identifier => $count) {
            if ($count > 1) {
                foreach ($forms as $index => $formMetadata) {
                    if ($formMetadata->identifier === $identifier) {
                        $forms[$index] = $formMetadata->withDuplicateIdentifier(true);
                    }
                }
            }
        }

        return $this->sortForms($forms, $formSettings, $searchCriteria->getOrderField(), $searchCriteria->getOrderDirection());
    }

    /**
     * Check if any forms are available
     */
    public function hasForms(array $formSettings): bool
    {
        foreach ($this->storageAdapterFactory->getAllAdapters() as $adapter) {
            try {
                $forms = $adapter->findAll(new SearchCriteria(limit: 1));

                if (!empty($forms)) {
                    return true;
                }
            } catch (\Exception) {
                continue;
            }
        }

        return false;
    }

    /**
     * Get accessible form storage folders
     * Delegates to FileStorageAdapter
     */
    public function getAccessibleFormStorageFolders(array $formSettings): array
    {
        if (!$this->storageAdapterFactory->hasAdapterType('filemount')) {
            return [];
        }

        $adapter = $this->storageAdapterFactory->getAdapterByType('filemount');

        if (method_exists($adapter, 'getAccessibleFormStorageFolders')) {
            return $adapter->getAccessibleFormStorageFolders();
        }

        return [];
    }

    /**
     * Get accessible extension folders
     * Delegates to FileStorageAdapter
     */
    public function getAccessibleExtensionFolders(array $formSettings): array
    {
        if (!$this->storageAdapterFactory->hasAdapterType('filemount')) {
            return [];
        }

        $adapter = $this->storageAdapterFactory->getAdapterByType('filemount');

        if (method_exists($adapter, 'getAccessibleExtensionFolders')) {
            return $adapter->getAccessibleExtensionFolders();
        }

        return [];
    }

    /**
     * Get unique persistence identifier for a new form
     */
    public function getUniquePersistenceIdentifier(string $formIdentifier, string $savePath, array $formSettings): string
    {
        $formPersistenceIdentifier = $savePath . $formIdentifier . self::FORM_DEFINITION_FILE_EXTENSION;

        if (!$this->exists($formPersistenceIdentifier, $formSettings)) {
            return $formPersistenceIdentifier;
        }

        for ($attempts = 1; $attempts < 100; $attempts++) {
            $formPersistenceIdentifier = $savePath . sprintf('%s_%d', $formIdentifier, $attempts) . self::FORM_DEFINITION_FILE_EXTENSION;
            if (!$this->exists($formPersistenceIdentifier, $formSettings)) {
                return $formPersistenceIdentifier;
            }
        }

        $formPersistenceIdentifier = $savePath . sprintf('%s_%d', $formIdentifier, time()) . self::FORM_DEFINITION_FILE_EXTENSION;
        if (!$this->exists($formPersistenceIdentifier, $formSettings)) {
            return $formPersistenceIdentifier;
        }

        throw new NoUniquePersistenceIdentifierException(
            sprintf('Could not find a unique persistence identifier for form identifier "%s" after %d attempts', $formIdentifier, $attempts),
            1476010403
        );
    }

    /**
     * Get unique identifier (not persistence identifier)
     */
    public function getUniqueIdentifier(array $formSettings, string $identifier): string
    {
        $originalIdentifier = $identifier;

        if ($this->checkForDuplicateIdentifier($formSettings, $identifier)) {
            for ($attempts = 1; $attempts < 100; $attempts++) {
                $identifier = sprintf('%s_%d', $originalIdentifier, $attempts);
                if (!$this->checkForDuplicateIdentifier($formSettings, $identifier)) {
                    return $identifier;
                }
            }

            $identifier = $originalIdentifier . '_' . time();
            if ($this->checkForDuplicateIdentifier($formSettings, $identifier)) {
                throw new NoUniqueIdentifierException(
                    sprintf('Could not find a unique identifier for form identifier "%s" after %d attempts', $identifier, $attempts),
                    1477688567
                );
            }
        }

        return $identifier;
    }

    /**
     * Check if persistence path is allowed
     */
    public function isAllowedPersistencePath(string $persistencePath, array $formSettings): bool
    {
        try {
            $identifier = new FormIdentifier($persistencePath);
            $adapter = $this->storageAdapterFactory->getAdapterForIdentifier($persistencePath);
            if (method_exists($adapter, 'isAllowedPersistencePath')) {
                return $adapter->isAllowedPersistencePath($persistencePath);
            }
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Check if file has valid extension
     */
    public function hasValidFileExtension(string $fileName): bool
    {
        return str_ends_with($fileName, self::FORM_DEFINITION_FILE_EXTENSION);
    }

    /**
     * Load form definition from storage
     */
    private function loadFromStorage(string $persistenceIdentifier): array
    {
        try {
            $identifier = new FormIdentifier($persistenceIdentifier);
            $adapter = $this->storageAdapterFactory->getAdapterForIdentifier($persistenceIdentifier);

            return $adapter->read($identifier)->toArray();
        } catch (\Exception $e) {
            return [
                'type' => 'Form',
                'identifier' => $persistenceIdentifier,
                'label' => $e->getMessage(),
                'invalid' => true,
            ];
        }
    }

    /**
     * Check if form with persistence identifier exists
     */
    private function exists(string $persistenceIdentifier, array $formSettings): bool
    {
        try {
            $identifier = new FormIdentifier($persistenceIdentifier);
            $adapter = $this->storageAdapterFactory->getAdapterForIdentifier($persistenceIdentifier);

            return $adapter->exists($identifier);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Check if a form with given identifier already exists in any storage
     */
    private function checkForDuplicateIdentifier(array $formSettings, string $identifier): bool
    {
        foreach ($this->storageAdapterFactory->getAllAdapters() as $adapter) {
            try {
                $forms = $adapter->findAll(new SearchCriteria());

                foreach ($forms as $formData) {
                    // Check the form identifier from the YAML definition
                    if ($formData->identifier === $identifier) {
                        return true;
                    }
                }
            } catch (\Exception) {
                continue;
            }
        }

        return false;
    }

    protected function sortForms(array $forms, array $formSettings, string $orderField = '', ?string $orderDirection = null): array
    {
        if ($orderDirection) {
            $ascending = $orderDirection === 'asc';
        } else {
            $ascending = $formSettings['persistenceManager']['sortAscending'] ?? true;
        }
        $sortMultiplier = $ascending ? 1 : -1;
        $keys = $orderField ? [$orderField] : $formSettings['persistenceManager']['sortByKeys'] ?? ['name', 'fileUid'];

        usort($forms, static function (FormMetadata $a, FormMetadata $b) use ($keys, $sortMultiplier) {
            foreach ($keys as $key) {
                $aValue = match ($key) {
                    'name' => $a->name,
                    'identifier' => $a->identifier,
                    'persistenceIdentifier' => $a->persistenceIdentifier,
                    'prototypeName' => $a->prototypeName,
                    default => null,
                };
                $bValue = match ($key) {
                    'name' => $b->name,
                    'identifier' => $b->identifier,
                    'persistenceIdentifier' => $b->persistenceIdentifier,
                    'prototypeName' => $b->prototypeName,
                    default => null,
                };

                if ($aValue !== null && $bValue !== null) {
                    $diff = strcasecmp((string)$aValue, (string)$bValue);
                    if ($diff) {
                        return $diff * $sortMultiplier;
                    }
                }
            }
            return 0;
        });
        return $forms;
    }

    /**
     * Clear cache for specific form
     */
    private function clearFormCache(string $persistenceIdentifier): void
    {
        $cacheKey = 'ext-form-load-' . hash('xxh3', $persistenceIdentifier);
        $this->runtimeCache->remove($cacheKey);
    }
}
