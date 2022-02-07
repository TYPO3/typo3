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

namespace TYPO3\CMS\Tstemplate\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;

/**
 * This class displays the Info/Modify screen of the Web > Template module
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TypoScriptTemplateInformationController extends TypoScriptTemplateModuleController
{
    /**
     * Gets the data for a row of a HTML table in the fluid template
     *
     * @param string $label The label to be shown (e.g. 'Title:')
     * @param string $data The data/information to be shown (e.g. 'Template for my site')
     * @param string $field The field/variable to be sent on clicking the edit icon (e.g. 'title')
     * @param int $id The field/variable to be sent on clicking the edit icon (e.g. 'title')
     * @return array Data for a row of a HTML table
     */
    protected function tableRowData(string $label, string $data, string $field, int $id): array
    {
        $urlParameters = [
            'id' => $this->id,
            'edit' => [
                'sys_template' => [
                    $id => 'edit',
                ],
            ],
            'columnsOnly' => $field,
            'createExtension' => 0,
            'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
        ];
        $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);

        return [
            'url' => $url,
            'data' => $data,
            'label' => $label,
        ];
    }

    /**
     * Fetch a template record on the current page. If $selectedTemplateRecord is given
     * and greater than zero, this record will be checked.
     */
    protected function initialize_editor(int $selectedTemplateRecord): bool
    {
        // Get the row of the first VISIBLE template of the page. where clause like the frontend.
        $this->templateRow = $this->getFirstTemplateRecordOnPage($this->id, $selectedTemplateRecord);
        if (is_array($this->templateRow)) {
            return true;
        }
        return false;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        // Fallback to regular module when on root level
        if ($this->id === 0) {
            return $this->overviewAction();
        }
        // Checking for more than one sys_template an if, set a menu
        $manyTemplatesMenu = $this->templateMenu();
        $selectedTemplateRecord = 0;
        if ($manyTemplatesMenu) {
            $selectedTemplateRecord = (int)$this->moduleData->get('templatesOnPage');
        }
        // Initialize
        $existTemplate = $this->initialize_editor($selectedTemplateRecord);
        $saveId = 0;
        if ($existTemplate) {
            $saveId = empty($this->templateRow['_ORIG_uid']) ? $this->templateRow['uid'] : $this->templateRow['_ORIG_uid'];
        }
        // Create extension template
        $newId = $this->createTemplate((int)$saveId);
        if ($newId) {
            // Switch to new template
            $urlParameters = [
                'id' => $this->id,
                'templatesOnPage' => $newId,
            ];
            $url = $this->uriBuilder->buildUriFromRoute($this->currentModule->getIdentifier(), $urlParameters);
            throw new PropagateResponseException(new RedirectResponse($url, 303), 1607271781);
        }
        if ($existTemplate) {
            $lang = $this->getLanguageService();
            $lang->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang_info.xlf');
            $assigns = [];
            $assigns['templateRecord'] = $this->templateRow;
            $assigns['manyTemplatesMenu'] = $manyTemplatesMenu;

            // Processing:
            $tableRows = [];
            $tableRows[] = $this->tableRowData($lang->getLL('title'), $this->templateRow['title'] ?? '', 'title', (int)$this->templateRow['uid']);
            $tableRows[] = $this->tableRowData($lang->getLL('description'), $this->templateRow['description'] ?? '', 'description', (int)$this->templateRow['uid']);
            $tableRows[] = $this->tableRowData($lang->getLL('constants'), sprintf($lang->getLL('editToView'), trim((string)$this->templateRow['constants']) ? count(explode(LF, (string)$this->templateRow['constants'])) : 0), 'constants', (int)$this->templateRow['uid']);
            $tableRows[] = $this->tableRowData($lang->getLL('setup'), sprintf($lang->getLL('editToView'), trim((string)$this->templateRow['config']) ? count(explode(LF, (string)$this->templateRow['config'])) : 0), 'config', (int)$this->templateRow['uid']);
            $assigns['tableRows'] = $tableRows;

            // Edit all icon:
            $urlParameters = [
                'edit' => [
                    'sys_template' => [
                        $this->templateRow['uid'] => 'edit',
                    ],
                ],
                'createExtension' => 0,
                'returnUrl' => $this->request->getAttribute('normalizedParams')->getRequestUri(),
            ];
            $assigns['editAllUrl'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);

            $this->view->assignMultiple($assigns);
            return $this->view->renderResponse('InformationModule');
        }
        return $this->noTemplateAction();
    }

    /**
     * Add additional "NEW" button to the button bar
     */
    protected function getButtons(): void
    {
        parent::getButtons();

        if ($this->id && $this->access) {
            $urlParameters = [
                'id' => $this->id,
                'template' => 'all',
                'createExtension' => 'new',
            ];
            $buttonBar = $this->view->getDocHeaderComponent()->getButtonBar();
            $newButton = $buttonBar->makeLinkButton()
                ->setHref((string)$this->uriBuilder->buildUriFromRoute($this->currentModule->getIdentifier(), $urlParameters))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:db_new.php.pagetitle'))
                ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
            $buttonBar->addButton($newButton);
        }
    }
}
