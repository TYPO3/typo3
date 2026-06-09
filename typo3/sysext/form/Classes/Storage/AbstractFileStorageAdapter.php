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

namespace TYPO3\CMS\Form\Storage;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Form\Domain\DTO\FormMetadata;
use TYPO3\CMS\Form\Domain\DTO\SearchCriteria;
use TYPO3\CMS\Form\Domain\ValueObject\FormIdentifier;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\NoUniquePersistenceIdentifierException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

/**
 * Abstract helper class for file-based form persistence
 *
 * Provides shared utility methods for file-based storage adapters.
 * Concrete storage adapters must implement StorageAdapterInterface.
 *
 * @internal
 */
abstract class AbstractFileStorageAdapter
{
    public const FORM_DEFINITION_FILE_EXTENSION = FormPersistenceManagerInterface::FORM_DEFINITION_FILE_EXTENSION;

    protected ?StorageRepository $storageRepository = null;

    public function injectStorageRepository(StorageRepository $storageRepository): void
    {
        $this->storageRepository = $storageRepository;
    }

    protected function hasValidFileExtension(string $identifier): bool
    {
        return str_ends_with($identifier, self::FORM_DEFINITION_FILE_EXTENSION);
    }

    abstract public function exists(FormIdentifier $identifier): bool;
    abstract public function existsByFormIdentifier(string $formIdentifier): bool;
    abstract public function findAll(SearchCriteria $criteria): array;

    /**
     * Build a user-friendly storageLocation label for display
     * Each storage adapter implements this to provide appropriate storageLocation information
     */
    abstract protected function buildStorageLocationLabel(string $persistenceIdentifier): string;

    /**
     * This takes a form identifier and returns a unique persistence identifier for it.
     * By default, this is just similar to the identifier. But if a form with the same persistence identifier already
     * exists a suffix is appended until the persistence identifier is unique.
     *
     * @param string $formIdentifier lowerCamelCased form identifier
     * @param string $storageLocation Path where the form should be saved (e.g., "1:/forms/")
     * @return string unique form persistence identifier (e.g., "1:/forms/contact.form.yaml")
     * @throws NoUniquePersistenceIdentifierException
     * @throws PersistenceManagerException
     */
    public function getUniquePersistenceIdentifier(string $formIdentifier, string $storageLocation): string
    {
        $storageLocation = rtrim($storageLocation, '/') . '/';
        $formPersistenceIdentifier = $storageLocation . $formIdentifier . self::FORM_DEFINITION_FILE_EXTENSION;

        if (!$this->exists(new FormIdentifier($formPersistenceIdentifier))) {
            return $formPersistenceIdentifier;
        }

        for ($attempts = 1; $attempts < 100; $attempts++) {
            $formPersistenceIdentifier = $storageLocation . sprintf('%s_%d', $formIdentifier, $attempts) . self::FORM_DEFINITION_FILE_EXTENSION;
            if (!$this->exists(new FormIdentifier($formPersistenceIdentifier))) {
                return $formPersistenceIdentifier;
            }
        }

        $formPersistenceIdentifier = $storageLocation . sprintf('%s_%d', $formIdentifier, time()) . self::FORM_DEFINITION_FILE_EXTENSION;
        if (!$this->exists(new FormIdentifier($formPersistenceIdentifier))) {
            return $formPersistenceIdentifier;
        }

        throw new NoUniquePersistenceIdentifierException(
            sprintf('Could not find a unique persistence identifier for form identifier "%s" after %d attempts', $formIdentifier, $attempts),
            1764879439
        );
    }

    protected function extractMetaDataFromCouldBeFormDefinition(string $maybeRawFormDefinition): array
    {
        $metaDataProperties = ['identifier', 'type', 'label', 'prototypeName'];
        $metaData = [];
        foreach (explode(LF, $maybeRawFormDefinition) as $line) {
            if (empty($line) || $line[0] === ' ') {
                continue;
            }
            $parts = explode(':', $line, 2);
            $key = trim($parts[0]);
            if (!($parts[1] ?? null) || !in_array($key, $metaDataProperties, true)) {
                continue;
            }
            if ($key === 'label') {
                try {
                    $parsedLabelLine = Yaml::parse($line);
                    $value = $parsedLabelLine['label'] ?? '';
                } catch (ParseException) {
                    $value = '';
                }
            } else {
                $value = trim($parts[1], " '\"\r");
            }
            $metaData[$key] = $value;
        }
        return $metaData;
    }

    /**
     * @throws PersistenceManagerException
     */
    protected function generateErrorsIfFormDefinitionIsInvalidOrHasInvalidFileExtension(array $formDefinition, string $identifier): void
    {
        if (!$this->looksLikeAFormDefinitionArray($formDefinition) || !$this->hasValidFileExtension($identifier)) {
            throw new PersistenceManagerException(sprintf('Form definition "%s" does not end with ".form.yaml".', $identifier), 1531160649);
        }
    }

    /**
     * Check if array looks like a form definition
     */
    protected function looksLikeAFormDefinitionArray(array $data): bool
    {
        return !empty($data['identifier']) && trim($data['type'] ?? '') === 'Form';
    }

    protected function looksLikeAFormDefinition(FormMetadata $formMetadata): bool
    {
        return !empty($formMetadata->identifier) && trim($formMetadata->type) === 'Form';
    }

    /**
     * Check if form data matches search criteria
     */
    protected function matchesCriteria(FormMetadata $formMetadata, SearchCriteria $criteria): bool
    {
        if ($criteria->searchTerm) {
            $searchIn = strtolower(
                $formMetadata->name . ' '
                . $formMetadata->identifier . ' '
                . $formMetadata->prototypeName . ' '
                . ($formMetadata->persistenceIdentifier ?? '')
            );

            if (!str_contains($searchIn, strtolower($criteria->searchTerm))) {
                return false;
            }
        }

        return true;
    }
}
