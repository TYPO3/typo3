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

namespace TYPO3\CMS\Form\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;
use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\ParseErrorException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

/**
 * Contains a preview rendering for the page module of CType="form_formframework"
 * @internal
 */
#[Autoconfigure(public: true)]
class FormPagePreviewRenderer extends StandardContentPreviewRenderer
{
    private const L10N_PREFIX = 'LLL:EXT:form/Resources/Private/Language/Database.xlf:';

    public function __construct(
        protected readonly FlexFormService $flexFormService,
        protected readonly FormPersistenceManagerInterface $formPersistenceManager,
        protected readonly FlashMessageService $flashMessageService,
        protected readonly ExtbaseConfigurationManagerInterface $extbaseConfigurationManager,
        protected readonly ExtFormConfigurationManagerInterface $extFormConfigurationManager,
    ) {}

    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $record = $item->getRecord();
        $itemContent = $this->linkEditContent('<strong>' . htmlspecialchars($item->getContext()->getContentTypeLabels()['form_formframework']) . '</strong>', $record) . '<br />';
        $persistenceIdentifier = null;
        if ($record->has('pi_flexform')) {
            $flexFormData = $record->get('pi_flexform');
            if ($flexFormData instanceof FlexFormFieldValues) {
                if ($flexFormData->has('sDEF/settings.persistenceIdentifier')) {
                    $persistenceIdentifier = $flexFormData->get('sDEF/settings.persistenceIdentifier');
                } else {
                    $this->logger?->warning(
                        'Field "pi_flexform" for record-uid "{uid}" does not contain a persistence identifier.',
                        ['uid' => $record->getUid()]
                    );
                }
            } else {
                $this->logger?->warning(
                    'Type "{type}" of field "pi_flexform" for record-uid "{uid}" is not valid.',
                    ['type' => get_debug_type($flexFormData), 'uid' => $record->getUid()]
                );
            }
        }
        $languageService = $this->getLanguageService();
        if (!empty($persistenceIdentifier)) {
            try {
                try {
                    if ($this->formPersistenceManager->hasValidFileExtension($persistenceIdentifier) || PathUtility::isExtensionPath($persistenceIdentifier)) {
                        // The ConfigurationManager of ext:form needs ext:extbase ConfigurationManager to retrieve basic TS
                        // settings (for "module.tx_form" allowed form storages). ConfigurationManager of extbase should *usually*
                        // only be called in extbase context and needs a Request, which is usually set by extbase bootstrap.
                        // We are however not in extbase context here.
                        // To prevent a fallback of extbase ConfigurationManager to $GLOBALS['TYPO3_REQUEST'], we set
                        // the request explicitly here, to then fetch $formSettings from ext:form ConfigurationManager.
                        $request = $item->getContext()->getCurrentRequest();
                        $this->extbaseConfigurationManager->setRequest($request);
                        $typoScriptSettings = $this->extbaseConfigurationManager->getConfiguration(ExtbaseConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'form');
                        $formSettings = $this->extFormConfigurationManager->getYamlConfiguration($typoScriptSettings, false);
                        $formDefinition = $this->formPersistenceManager->load($persistenceIdentifier, $formSettings);
                        $formLabel = $formDefinition['label'];
                    } else {
                        $formLabel = sprintf(
                            $languageService->sL(self::L10N_PREFIX . 'tt_content.preview.inaccessiblePersistenceIdentifier'),
                            $persistenceIdentifier
                        );
                    }
                } catch (ParseErrorException $e) {
                    $formLabel = sprintf(
                        $languageService->sL(self::L10N_PREFIX . 'tt_content.preview.invalidPersistenceIdentifier'),
                        $persistenceIdentifier
                    );
                } catch (PersistenceManagerException $e) {
                    $formLabel = sprintf(
                        $languageService->sL(self::L10N_PREFIX . 'tt_content.preview.inaccessiblePersistenceIdentifier'),
                        $persistenceIdentifier
                    );
                } catch (Exception $e) {
                    $formLabel = sprintf(
                        $languageService->sL(self::L10N_PREFIX . 'tt_content.preview.notExistingdPersistenceIdentifier'),
                        $persistenceIdentifier
                    );
                }
            } catch (NoSuchFileException $e) {
                $this->addInvalidFrameworkConfigurationFlashMessage($persistenceIdentifier, $e);
                $formLabel = sprintf(
                    $languageService->sL(self::L10N_PREFIX . 'tt_content.preview.notExistingdPersistenceIdentifier'),
                    $persistenceIdentifier
                );
            } catch (ParseErrorException $e) {
                $this->addInvalidFrameworkConfigurationFlashMessage($persistenceIdentifier, $e);
                $formLabel = sprintf(
                    $languageService->sL(self::L10N_PREFIX . 'tt_content.preview.invalidFrameworkConfiguration'),
                    $persistenceIdentifier
                );
            } catch (\Exception $e) {
                // Top level catch - FAL throws top level exceptions on missing files, eg. in getFileInfoByIdentifier() of LocalDriver
                $this->addInvalidFrameworkConfigurationFlashMessage($persistenceIdentifier, $e);
                $formLabel = sprintf(
                    $languageService->sL(self::L10N_PREFIX . 'tt_content.preview.invalidFrameworkConfiguration.text'),
                    $persistenceIdentifier,
                    $e->getMessage()
                );
            }
        } else {
            $formLabel = $languageService->sL(self::L10N_PREFIX . 'tt_content.preview.noPersistenceIdentifier');
        }
        $itemContent .= $this->linkEditContent(htmlspecialchars($formLabel), $record) . '<br />';
        return $itemContent;
    }

    protected function addInvalidFrameworkConfigurationFlashMessage(string $persistenceIdentifier, \Exception $e): void
    {
        $languageService = $this->getLanguageService();
        $this->flashMessageService
            ->getMessageQueueByIdentifier('core.template.flashMessages')
            ->enqueue(
                GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf(
                        $languageService->sL(self::L10N_PREFIX . 'tt_content.preview.invalidFrameworkConfiguration.text'),
                        $persistenceIdentifier,
                        $e->getMessage()
                    ),
                    $languageService->sL(self::L10N_PREFIX . 'tt_content.preview.invalidFrameworkConfiguration.title'),
                    ContextualFeedbackSeverity::ERROR,
                    true
                )
            );
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
