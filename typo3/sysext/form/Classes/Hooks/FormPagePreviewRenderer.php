<?php
declare(strict_types = 1);
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

use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\ParseErrorException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManager;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

/**
 * Contains a preview rendering for the page module of CType="form_formframework"
 * @internal
 */
class FormPagePreviewRenderer implements PageLayoutViewDrawItemHookInterface
{
    /**
     * Localisation prefix
     */
    const L10N_PREFIX = 'LLL:EXT:form/Resources/Private/Language/Database.xlf:';

    /**
     * Preprocesses the preview rendering of the content element "form_formframework".
     *
     * @param \TYPO3\CMS\Backend\View\PageLayoutView $parentObject Calling parent object
     * @param bool $drawItem Whether to draw the item using the default functionalities
     * @param string $headerContent Header content
     * @param string $itemContent Item content
     * @param array $row Record row of tt_content
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

            $persistenceIdentifier = $flexFormData['settings']['persistenceIdentifier'];
            if (!empty($persistenceIdentifier)) {
                try {
                    $formPersistenceManager = GeneralUtility::makeInstance(ObjectManager::class)->get(FormPersistenceManagerInterface::class);

                    try {
                        if (
                            StringUtility::endsWith($persistenceIdentifier, FormPersistenceManager::FORM_DEFINITION_FILE_EXTENSION)
                            || strpos($persistenceIdentifier, 'EXT:') === 0
                        ) {
                            $formDefinition = $formPersistenceManager->load($persistenceIdentifier);
                            $formLabel = $formDefinition['label'];
                        } else {
                            $formLabel = sprintf(
                                $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.inaccessiblePersistenceIdentifier'),
                                $persistenceIdentifier
                            );
                        }
                    } catch (ParseErrorException $e) {
                        $formLabel = sprintf(
                            $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.invalidPersistenceIdentifier'),
                            $persistenceIdentifier
                        );
                    } catch (PersistenceManagerException $e) {
                        $formLabel = sprintf(
                            $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.inaccessiblePersistenceIdentifier'),
                            $persistenceIdentifier
                        );
                    } catch (Exception $e) {
                        $formLabel = sprintf(
                            $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.notExistingdPersistenceIdentifier'),
                            $persistenceIdentifier
                        );
                    }
                } catch (NoSuchFileException $e) {
                    $this->addInvalidFrameworkConfigurationFlashMessage($e);
                    $formLabel = sprintf(
                        $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.notExistingdPersistenceIdentifier'),
                        $persistenceIdentifier
                    );
                } catch (ParseErrorException $e) {
                    $this->addInvalidFrameworkConfigurationFlashMessage($e);
                    $formLabel = sprintf(
                        $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.invalidFrameworkConfiguration'),
                        $persistenceIdentifier
                    );
                } catch (\Exception $e) {
                    // Top level catch - FAL throws top level exceptions on missing files, eg. in getFileInfoByIdentifier() of LocalDriver
                    $this->addInvalidFrameworkConfigurationFlashMessage($e);
                    $formLabel = $e->getMessage();
                }
            } else {
                $formLabel = $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.noPersistenceIdentifier');
            }

            $itemContent .= $parentObject->linkEditContent(
                $parentObject->renderText($formLabel),
                $row
            ) . '<br />';

            $drawItem = false;
        }
    }

    /**
     * @param \Exception $e
     */
    protected function addInvalidFrameworkConfigurationFlashMessage(\Exception $e)
    {
        $messageText = sprintf(
            $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.invalidFrameworkConfiguration.text'),
            $e->getMessage()
        );

        GeneralUtility::makeInstance(ObjectManager::class)
            ->get(FlashMessageService::class)
            ->getMessageQueueByIdentifier('core.template.flashMessages')
            ->enqueue(
                GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $messageText,
                    $this->getLanguageService()->sL(self::L10N_PREFIX . 'tt_content.preview.invalidFrameworkConfiguration.title'),
                    AbstractMessage::ERROR,
                    true
                )
            );
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
