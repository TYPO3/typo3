<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Hooks;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Contains a preview rendering for the page module of CType="form_formframework"
 */
class FormPagePreviewRenderer implements \TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface
{
    /**
     * Preprocesses the preview rendering of the content element "form_formframework".
     *
     * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject Calling parent object
     * @param bool $drawItem Whether to draw the item using the default functionalities
     * @param string $headerContent Header content
     * @param string $itemContent Item content
     * @param array $row Record row of tt_content
     * @return void
     */
    public function preProcess(
        \TYPO3\CMS\Backend\View\PageLayoutView &$parentObject,
        &$drawItem,
        &$headerContent,
        &$itemContent,
        array &$row
    ) {
        if ($row['CType'] === 'form_formframework') {
            $contentType = $parentObject->CType_labels[$row['CType']];
            $itemContent .= $parentObject->linkEditContent('<strong>' . htmlspecialchars($contentType) . '</strong>', $row) . '<br />';

            $flexFormData = GeneralUtility::makeInstance(FlexFormService::class)
                ->convertFlexFormContentToArray($row['pi_flexform']);

            if (!empty($flexFormData['settings']['persistenceIdentifier'])) {
                $persistenceIdentifier = $flexFormData['settings']['persistenceIdentifier'];
                if (empty($persistenceIdentifier)) {
                    $formLabel = $this->getLanguageService()->sL(
                        'LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.preview.noPersistenceIdentifier'
                    );
                } else {
                    $formPersistenceManager = GeneralUtility::makeInstance(ObjectManager::class)->get(FormPersistenceManagerInterface::class);
                    $formDefinition = $formPersistenceManager->load($persistenceIdentifier);
                    $formLabel = $formDefinition['label'];
                }
            } else {
                $formLabel = $this->getLanguageService()->sL(
                    'LLL:EXT:form/Resources/Private/Language/Database.xlf:tt_content.preview.noPersistenceIdentifier'
                );
            }

            $itemContent .= $parentObject->linkEditContent(
                $parentObject->renderText($formLabel),
                $row
            ) . '<br />';

            $drawItem = false;
        }
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
