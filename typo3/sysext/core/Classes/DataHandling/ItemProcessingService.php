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

namespace TYPO3\CMS\Core\DataHandling;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides services around item processing
 */
#[Autoconfigure(public: true)]
readonly class ItemProcessingService
{
    public function __construct(
        protected SiteFinder $siteFinder,
        protected TcaSchemaFactory $tcaSchemaFactory,
        protected FlashMessageService $flashMessageService,
    ) {}

    public function processItems(SelectItemCollection $items, ItemsProcessorContext $context): SelectItemCollection
    {
        $pageId = (int)($context->table === 'pages' ? ($context->row['uid'] ?? $context->realPid) : ($context->row['pid'] ?? $context->realPid));
        $fieldTSconfig = $context->fieldTSconfig;
        if ($fieldTSconfig === []) {
            $TSconfig = BackendUtility::getPagesTSconfig($pageId);
            $fieldTSconfig = $TSconfig['TCEFORM.'][$context->table . '.'][$context->field . '.'] ?? [];
        }

        $site = $context->site;
        // Legacy itemsProcFunc support - convert to array for backwards compatibility
        $itemsArray = $items->toArray();
        $processorParameters = [
            // Function manipulates $items directly and return nothing
            'items' => &$itemsArray,
            'config' => $context->fieldConfiguration,
            'table' => $context->table,
            'row' => $context->row,
            'field' => $context->field,
            'effectivePid' => $context->realPid,
            'site' => $site,
        ];
        $processorParameters = array_merge($processorParameters, $context->additionalParameters);
        try {
            // @todo: deprecate when the time is right
            if (!empty($context->fieldConfiguration['itemsProcFunc'])) {
                $processorParameters['TSconfig'] = $fieldTSconfig['itemsProcFunc.'] ?? null;
                GeneralUtility::callUserFunction($context->fieldConfiguration['itemsProcFunc'], $processorParameters, $this);
                // Recreate collection from potentially modified array
                $items = SelectItemCollection::createFromArray($itemsArray, $context->fieldConfiguration['type']);
            }

            // "itemsProcessors" is the more modern version of "itemsProcFunc", which will eventually be deprecated
            $itemsProcessors = $context->fieldConfiguration['itemsProcessors'] ?? [];
            ksort($itemsProcessors);
            foreach ($itemsProcessors as $key => $itemsProcessorConfiguration) {
                $tsConfig = $fieldTSconfig['itemsProcessors.'][$key . '.'] ?? [];
                if (empty($itemsProcessorConfiguration['class'])) {
                    throw new ItemsProcessorExecutionFailedException(
                        $itemsArray,
                        sprintf(
                            'Missing class for itemsProcessors %d, field %s, table %s',
                            $key,
                            $context->field,
                            $context->table
                        ),
                        1761814167
                    );
                }
                $itemsProcessorObject = GeneralUtility::makeInstance($itemsProcessorConfiguration['class']);
                if (!$itemsProcessorObject instanceof ItemsProcessorInterface) {
                    throw new ItemsProcessorExecutionFailedException(
                        $itemsArray,
                        sprintf(
                            'Class %s must implement %s',
                            $itemsProcessorConfiguration['class'],
                            ItemsProcessorInterface::class
                        ),
                        1761753898
                    );
                }
                $processorContext = new ItemsProcessorContext(
                    table: $context->table,
                    field: $context->field,
                    row: $context->row,
                    fieldConfiguration: $context->fieldConfiguration,
                    processorParameters: $itemsProcessorConfiguration['parameters'] ?? [],
                    realPid: $context->realPid,
                    site: $site,
                    fieldTSconfig: $tsConfig,
                    additionalParameters: $context->additionalParameters
                );
                $items = $itemsProcessorObject->processItems($items, $processorContext);
            }
        } catch (\Exception $exception) {
            // Catch anything here!
            throw new ItemsProcessorExecutionFailedException($itemsArray, $exception->getMessage(), 1761588907, $exception);
        }
        return $items;
    }

    /**
     * Executes an itemsProcFunc or itemsProcessors if defined in TCA and returns the combined result
     * (predefined + processed items)
     *
     * @param string $table
     * @param int $realPid Record pid. This is the pid of the record.
     * @param string $field
     * @param array $row
     * @param array $tcaConfig The TCA configuration of $field
     * @param array $selectedItems The items already defined in the TCA configuration
     * @return array The processed items (including the predefined items)
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \TYPO3\CMS\Core\Schema\Exception\UndefinedFieldException
     * @throws \TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException
     */
    public function getProcessingItems($table, $realPid, $field, $row, $tcaConfig, $selectedItems)
    {
        try {
            $itemsCollection = SelectItemCollection::createFromArray($selectedItems, $tcaConfig['type']);
            $context = new ItemsProcessorContext(
                table: $table,
                field: $field,
                row: $row,
                fieldConfiguration: $tcaConfig,
                processorParameters: [],
                realPid: $realPid,
                site: $this->resolveSite((int)($table === 'pages' ? ($row['uid'] ?? $realPid) : ($row['pid'] ?? $realPid)))
            );
            $selectedItems = $this->processItems($itemsCollection, $context)->toArray();
        } catch (ItemsProcessorExecutionFailedException $exception) {
            $fieldLabel = '';
            if ($this->tcaSchemaFactory->has($table)) {
                $schema = $this->tcaSchemaFactory->get($table);
                if ($schema->hasField($field)) {
                    $fieldLabel = $this->getLanguageService()->sL($schema->getField($field)->getLabel());
                }
            }
            if (!$fieldLabel) {
                $fieldLabel = $field;
            }
            $message = sprintf(
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.items_proc_func_error'),
                $fieldLabel,
                $exception->getMessage()
            );
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                '',
                ContextualFeedbackSeverity::ERROR,
                true
            );
            $defaultFlashMessageQueue = $this->flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        return $selectedItems;
    }

    public function resolveSite(int $pageId): SiteInterface
    {
        try {
            return $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException $e) {
            return new NullSite();
        }
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
