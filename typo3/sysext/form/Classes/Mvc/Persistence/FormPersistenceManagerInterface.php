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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Mvc\Persistence;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;

/**
 * The form persistence manager interface
 *
 * Scope: frontend / backend
 *
 * @internal
 */
interface FormPersistenceManagerInterface
{
    public const FORM_DEFINITION_FILE_EXTENSION = '.form.yaml';

    /**
     * Load the array form representation identified by $persistenceIdentifier, and return it.
     *
     * @param ?array $typoScriptSettings FE TS "plugin.tx_form.settings" - Given when rendering a form
     *        as plugin using FormFrontendController or formvh:render, empty array in all BE usages.
     *        Intended to override details like labels of single forms.
     */
    public function load(string $persistenceIdentifier, array $formSettings, ?array $typoScriptSettings = null, ?ServerRequestInterface $request = null): array;

    /**
     * Save the array form representation identified by $persistenceIdentifier
     *
     * @throws PersistenceManagerException
     */
    public function save(string $persistenceIdentifier, array $formDefinition, array $formSettings);

    /**
     * Delete the form representation identified by $persistenceIdentifier
     *
     * @throws PersistenceManagerException
     */
    public function delete(string $persistenceIdentifier, array $formSettings): void;

    /**
     * List all form definitions which can be loaded through this form persistence
     * manager.
     *
     * Returns an associative array with each item containing the keys 'name' (the human-readable name of the form)
     * and 'persistenceIdentifier' (the unique identifier for the Form Persistence Manager e.g. the path to the saved form definition).
     *
     * @return array in the format [['name' => 'Form 01', 'persistenceIdentifier' => 'path1'], [ .... ]]
     */
    public function listForms(array $formSettings): array;

    /**
     * Check if any form definition is available
     */
    public function hasForms(array $formSettings): bool;

    /**
     * Return a list of all accessible file mount points
     *
     * @return Folder[]
     */
    public function getAccessibleFormStorageFolders(array $formSettings): array;

    /**
     * Return a list of all accessible extension folders
     */
    public function getAccessibleExtensionFolders(array $formSettings): array;

    /**
     * This takes a form identifier and returns a unique persistence identifier for it.
     */
    public function getUniquePersistenceIdentifier(string $formIdentifier, string $savePath, array $formSettings): string;

    public function getUniqueIdentifier(array $formSettings, string $identifier): string;

    public function isAllowedPersistencePath(string $persistencePath, array $formSettings): bool;

    public function hasValidFileExtension(string $fileName): bool;
}
