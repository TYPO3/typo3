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

namespace TYPO3\CMS\Form\FormEngine\FieldWizard;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Form\Service\DatabaseService;
use TYPO3\CMS\Form\Service\FormEditorLinkProvider;

/**
 * Renders a labelled button below the form selection of the form content
 * element, which opens the selected form in the form editor. Beside it,
 * editors learn that a change affects every content element using the
 * form, and that forms are not versioned in workspaces.
 *
 * @internal
 */
final class EditFormInFormEditor extends AbstractNode
{
    public function __construct(
        private readonly FormEditorLinkProvider $formEditorLinkProvider,
        private readonly IconFactory $iconFactory,
        private readonly DatabaseService $databaseService,
    ) {}

    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        if (($this->data['command'] ?? '') !== 'edit') {
            return $resultArray;
        }
        $parameterArray = $this->data['parameterArray'];
        $persistenceIdentifier = (string)(((array)($parameterArray['itemFormElValue'] ?? []))[0] ?? '');
        $editUri = $this->formEditorLinkProvider->getEditUri($persistenceIdentifier, $this->getReturnUrl());
        if ($editUri === null) {
            return $resultArray;
        }
        $id = StringUtility::getUniqueId('t3js-formengine-fieldwizard-');
        $attributes = [
            'id' => $id,
            'href' => (string)$editUri,
            'class' => 'btn btn-default btn-sm',
            'data-element' => $parameterArray['itemFormElName'],
            'data-persistence-identifier' => $persistenceIdentifier,
        ];
        $languageService = $this->getLanguageService();
        $html = [];
        $html[] = '<div class="mt-2">';
        $html[] = '<a ' . GeneralUtility::implodeAttributes($attributes, true) . '>'
            . $this->iconFactory->getIcon('actions-open', IconSize::SMALL)->render()
            . ' ' . htmlspecialchars($languageService->sL('form.database:tt_content.pi_flexform.formframework.editInFormEditor'))
            . '</a>';
        foreach ($this->getNotices($persistenceIdentifier) as $notice) {
            $html[] = '<div class="mt-2"><span class="badge badge-info text-wrap text-start lh-sm">' . htmlspecialchars($notice) . '</span></div>';
        }
        $html[] = '</div>';
        $resultArray['html'] = implode(LF, $html);
        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@typo3/form/backend/form-engine/edit-form-control.js'
        )->instance('#' . $id);
        return $resultArray;
    }

    /**
     * @return list<string>
     */
    private function getNotices(string $persistenceIdentifier): array
    {
        $languageService = $this->getLanguageService();
        $notices = [];
        $usages = [];
        foreach ($this->databaseService->getReferencesByPersistenceIdentifier($persistenceIdentifier) as $reference) {
            $usages[$reference['tablename'] . ':' . $reference['recuid']] = true;
        }
        if (count($usages) > 1) {
            $notices[] = sprintf($languageService->sL('form.database:tt_content.pi_flexform.formframework.usage'), count($usages));
        }
        if ($this->getBackendUser()->workspace !== 0) {
            $notices[] = $languageService->sL('form.database:tt_content.pi_flexform.formframework.workspaceNotice');
        }
        return $notices;
    }

    /**
     * FormEngine hands over the URL of the current editing view as returnUrl.
     * The contextual edit sheet of the page module cannot be reopened on its
     * own, so the form editor returns to where the sheet returns to.
     */
    private function getReturnUrl(): string
    {
        $currentEditingUrl = (string)($this->data['returnUrl'] ?? '');
        $request = $this->data['request'] ?? null;
        if (!$request instanceof ServerRequestInterface
            || $request->getAttribute('route')?->getOption('_identifier') !== 'record_edit_contextual'
        ) {
            return $currentEditingUrl;
        }
        parse_str((string)parse_url($currentEditingUrl, PHP_URL_QUERY), $query);
        return is_string($query['returnUrl'] ?? null) ? $query['returnUrl'] : '';
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
