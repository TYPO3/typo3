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

namespace TYPO3\CMS\Form\Service;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Form\Domain\Configuration\PersistenceConfigurationService;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

/**
 * Decides whether the current backend user may open a form definition in the
 * form editor, and builds the link to it. Places outside the form module that
 * offer to edit a form ask this service instead of checking access themselves.
 *
 * It only decides whether a link is offered: the form editor checks access
 * again when it is opened.
 *
 * @internal
 */
final readonly class FormEditorLinkProvider
{
    private const FORM_EDITOR_MODULE = 'form_editor';

    public function __construct(
        private UriBuilder $uriBuilder,
        private ModuleProvider $moduleProvider,
        private FormPersistenceManagerInterface $formPersistenceManager,
        private PersistenceConfigurationService $persistenceConfigurationService,
    ) {}

    public function canEdit(string $persistenceIdentifier): bool
    {
        if ($persistenceIdentifier === '') {
            return false;
        }
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if (!$backendUser instanceof BackendUserAuthentication
            || !$this->moduleProvider->accessGranted(self::FORM_EDITOR_MODULE, $backendUser)
        ) {
            return false;
        }
        try {
            if (PathUtility::isExtensionPath($persistenceIdentifier)
                && !$this->persistenceConfigurationService->isAllowedToSaveToExtensionPaths()
            ) {
                return false;
            }
            return $this->formPersistenceManager->isAllowedPersistenceIdentifier($persistenceIdentifier);
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Returns null if the current backend user may not edit the form.
     */
    public function getEditUri(string $persistenceIdentifier, string $returnUrl = ''): ?UriInterface
    {
        if (!$this->canEdit($persistenceIdentifier)) {
            return null;
        }
        $parameters = ['formPersistenceIdentifier' => $persistenceIdentifier];
        if ($returnUrl !== '') {
            $parameters['returnUrl'] = $returnUrl;
        }
        return $this->uriBuilder->buildUriFromRoute(self::FORM_EDITOR_MODULE, $parameters);
    }
}
