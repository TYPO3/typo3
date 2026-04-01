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
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Form\Domain\DTO\FormData;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Domain\DTO\StorageContext;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;
use TYPO3\CMS\Form\Mvc\Configuration\TypoScriptService;
use TYPO3\CMS\Form\Mvc\Persistence\Event\AfterFormDefinitionLoadedEvent;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniqueIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Service\DatabaseService;
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
        private DatabaseService $databaseService,
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
     *
     * @throws PersistenceManagerException
     */
    public function save(string $persistenceIdentifier, array $formDefinition, array $formSettings, ?string $storageLocation = null): FormIdentifier
    {
        if (!$this->isAllowedPersistenceIdentifier($persistenceIdentifier)) {
            throw new PersistenceManagerException(
                sprintf('Save to path "%s" is not allowed.', $persistenceIdentifier),
                1477680881
            );
        }

        $identifier = new FormIdentifier($persistenceIdentifier);
        $adapter = $this->storageAdapterFactory->getAdapterForIdentifier($persistenceIdentifier);
        $formData = FormData::fromArray($formDefinition);

        $context = null;
        if (MathUtility::canBeInterpretedAsInteger($storageLocation)) {
            $context = StorageContext::create((int)$storageLocation);
        }

        $savedIdentifier = $adapter->write($identifier, $formData, $context);

        $this->clearFormCache($savedIdentifier->identifier);
        return $savedIdentifier;
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

        $allReferencesForFileUid = $this->databaseService->getAllReferencesForFileUid();
        $allReferencesForPersistenceIdentifier = $this->databaseService->getAllReferencesForPersistenceIdentifier();
        $allReferencesForFormDefinitionUid = $this->databaseService->getAllReferencesForFormDefinitionUid();

        foreach ($forms as $index => $formMetadata) {
            if (isset($formMetadata->fileUid) && array_key_exists($formMetadata->fileUid, $allReferencesForFileUid)) {
                $referenceCount = $allReferencesForFileUid[$formMetadata->fileUid];
            } elseif ($formMetadata->persistenceIdentifier && array_key_exists($formMetadata->persistenceIdentifier, $allReferencesForFormDefinitionUid)) {
                $referenceCount = $allReferencesForFormDefinitionUid[$formMetadata->persistenceIdentifier];
            } elseif ($formMetadata->persistenceIdentifier && array_key_exists($formMetadata->persistenceIdentifier, $allReferencesForPersistenceIdentifier)) {
                $referenceCount = $allReferencesForPersistenceIdentifier[$formMetadata->persistenceIdentifier];
            } else {
                $referenceCount = 0;
            }
            if ($referenceCount > 0) {
                $forms[$index] = $formMetadata->withReferenceCount($referenceCount);
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
     * Get unique persistence identifier for a new form
     */
    public function getUniquePersistenceIdentifier(string $storage, string $formIdentifier, ?string $savePath): string
    {
        return $this->storageAdapterFactory->getAdapterByType($storage)->getUniquePersistenceIdentifier($formIdentifier, $savePath);
    }

    /**
     * Get unique identifier (not persistence identifier)
     */
    public function getUniqueIdentifier(string $identifier): string
    {
        $originalIdentifier = $identifier;

        if ($this->checkForDuplicateIdentifier($identifier)) {
            for ($attempts = 1; $attempts < 100; $attempts++) {
                $identifier = sprintf('%s_%d', $originalIdentifier, $attempts);
                if (!$this->checkForDuplicateIdentifier($identifier)) {
                    return $identifier;
                }
            }

            $identifier = $originalIdentifier . '_' . time();
            if ($this->checkForDuplicateIdentifier($identifier)) {
                throw new NoUniqueIdentifierException(
                    sprintf('Could not find a unique identifier for form identifier "%s" after %d attempts', $identifier, $attempts),
                    1477688567
                );
            }
        }

        return $identifier;
    }

    /**
     * Check if a storage location is allowed
     *
     * For database storage: storageLocation is a PID
     * For file storage: storageLocation is a folder path (e.g., "1:/forms/")
     */
    public function isAllowedStorageLocation(string $storageLocation): bool
    {
        try {
            $adapter = $this->storageAdapterFactory->getAdapterForIdentifier($storageLocation);
            return $adapter->isAllowedStorageLocation($storageLocation);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Check if a persistence identifier is allowed
     *
     * For database storage: identifier is a UID or NEW*
     * For file storage: identifier is a full file path (e.g., "1:/forms/contact.form.yaml")
     */
    public function isAllowedPersistenceIdentifier(string $persistenceIdentifier): bool
    {
        try {
            $adapter = $this->storageAdapterFactory->getAdapterForIdentifier($persistenceIdentifier);
            return $adapter->isAllowedPersistenceIdentifier($persistenceIdentifier);
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
    private function exists(string $persistenceIdentifier): bool
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
    private function checkForDuplicateIdentifier(string $identifier): bool
    {
        foreach ($this->storageAdapterFactory->getAllAdapters() as $adapter) {
            try {
                if ($adapter->existsByFormIdentifier($identifier)) {
                    return true;
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
                $aValue = $a->getSortableValue($key);
                $bValue = $b->getSortableValue($key);

                if ($aValue === null || $bValue === null) {
                    continue;
                }

                $diff = (is_int($aValue) && is_int($bValue))
                    ? $aValue - $bValue
                    : strcasecmp((string)$aValue, (string)$bValue);

                if ($diff !== 0) {
                    return $diff * $sortMultiplier;
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

    public function getAccessibleStorageAdapters(): array
    {
        $storageAdapters = [];
        foreach ($this->storageAdapterFactory->getAllAdapters() as $adapter) {
            if ($adapter->isAccessible() === false) {
                continue;
            }
            $storageAdapters[] = [
                'typeIdentifier' => $adapter->getTypeIdentifier(),
                'label' => $adapter->getLabel(),
                'description' => $adapter->getDescription(),
                'iconIdentifier' => $adapter->getIconIdentifier(),
                'options' => $adapter->getFormManagerOptions(),
            ];
        }
        return $storageAdapters;
    }
}
