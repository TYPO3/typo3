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

namespace TYPO3\CMS\Form\Preview;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Preview\PreviewRendererInterface;
use TYPO3\CMS\Backend\Preview\RecordFieldPreviewProcessor;
use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;
use TYPO3\CMS\Core\Error\Exception;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\NoSuchFileException;
use TYPO3\CMS\Form\Mvc\Configuration\Exception\ParseErrorException;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;

/**
 * Contains a preview rendering for the page module of CType="form_formframework"
 * @internal
 */
#[Autoconfigure(public: true)]
final readonly class FormPagePreviewRenderer implements PreviewRendererInterface
{
    public function __construct(
        private FormPersistenceManagerInterface $formPersistenceManager,
        private FlashMessageService $flashMessageService,
        private RecordFieldPreviewProcessor $fieldProcessor,
        private StandardContentPreviewRenderer $standardContentPreviewRenderer,
        private LoggerInterface $logger,
    ) {}

    public function renderPageModulePreviewHeader(GridColumnItem $item): string
    {
        return $this->standardContentPreviewRenderer->renderPageModulePreviewHeader($item);
    }

    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $record = $item->getRecord();
        $request = $item->getContext()->getCurrentRequest();
        $persistenceIdentifier = null;
        if ($record->has('pi_flexform')) {
            $flexFormData = $record->get('pi_flexform');
            if ($flexFormData instanceof FlexFormFieldValues) {
                if ($flexFormData->has('sDEF/settings.persistenceIdentifier')) {
                    $persistenceIdentifier = $flexFormData->get('sDEF/settings.persistenceIdentifier');
                } else {
                    $this->logger->warning(
                        'Field "pi_flexform" for record-uid "{uid}" does not contain a persistence identifier.',
                        ['uid' => $record->getUid()]
                    );
                }
            } else {
                $this->logger->warning(
                    'Type "{type}" of field "pi_flexform" for record-uid "{uid}" is not valid.',
                    ['type' => get_debug_type($flexFormData), 'uid' => $record->getUid()]
                );
            }
        }
        $languageService = $this->getLanguageService();
        if (!empty($persistenceIdentifier)) {
            try {
                try {
                    $formDefinition = $this->formPersistenceManager->load($persistenceIdentifier);
                    $formLabel = $formDefinition['label'];
                } catch (ParseErrorException $e) {
                    $formLabel = sprintf(
                        $languageService->sL('form.database:tt_content.preview.invalidPersistenceIdentifier'),
                        $persistenceIdentifier
                    );
                } catch (PersistenceManagerException $e) {
                    $formLabel = sprintf(
                        $languageService->sL('form.database:tt_content.preview.inaccessiblePersistenceIdentifier'),
                        $persistenceIdentifier
                    );
                } catch (Exception $e) {
                    $formLabel = sprintf(
                        $languageService->sL('form.database:tt_content.preview.notExistingdPersistenceIdentifier'),
                        $persistenceIdentifier
                    );
                }
            } catch (NoSuchFileException $e) {
                $this->addInvalidFrameworkConfigurationFlashMessage($persistenceIdentifier, $e);
                $formLabel = sprintf(
                    $languageService->sL('form.database:tt_content.preview.notExistingdPersistenceIdentifier'),
                    $persistenceIdentifier
                );
            } catch (ParseErrorException $e) {
                $this->addInvalidFrameworkConfigurationFlashMessage($persistenceIdentifier, $e);
                $formLabel = sprintf(
                    $languageService->sL('form.database:tt_content.preview.invalidFrameworkConfiguration'),
                    $persistenceIdentifier
                );
            } catch (\Exception $e) {
                // Top level catch - FAL throws top level exceptions on missing files, eg. in getFileInfoByIdentifier() of LocalDriver
                $this->addInvalidFrameworkConfigurationFlashMessage($persistenceIdentifier, $e);
                $formLabel = sprintf(
                    $languageService->sL('form.database:tt_content.preview.invalidFrameworkConfiguration.text'),
                    $persistenceIdentifier,
                    $e->getMessage()
                );
            }
        } else {
            $formLabel = $languageService->sL('form.database:tt_content.preview.noPersistenceIdentifier');
        }
        $itemContent = '<strong>' . htmlspecialchars($item->getContext()->getContentTypeLabels()['form_formframework']) . '</strong><br />';
        return $this->fieldProcessor->linkToEditForm($itemContent . htmlspecialchars($formLabel), $record, $request);
    }

    public function renderPageModulePreviewFooter(GridColumnItem $item): string
    {
        return $this->standardContentPreviewRenderer->renderPageModulePreviewFooter($item);
    }

    public function wrapPageModulePreview(string $previewHeader, string $previewContent, GridColumnItem $item): string
    {
        return $this->standardContentPreviewRenderer->wrapPageModulePreview($previewHeader, $previewContent, $item);
    }

    private function addInvalidFrameworkConfigurationFlashMessage(string $persistenceIdentifier, \Exception $e): void
    {
        $languageService = $this->getLanguageService();
        $this->flashMessageService
            ->getMessageQueueByIdentifier('core.template.flashMessages')
            ->enqueue(
                new FlashMessage(
                    sprintf(
                        $languageService->sL('form.database:tt_content.preview.invalidFrameworkConfiguration.text'),
                        $persistenceIdentifier,
                        $e->getMessage()
                    ),
                    $languageService->sL('form.database:tt_content.preview.invalidFrameworkConfiguration.title'),
                    ContextualFeedbackSeverity::ERROR,
                    true
                )
            );
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
