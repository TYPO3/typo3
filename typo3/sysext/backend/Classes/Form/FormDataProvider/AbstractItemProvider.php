<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Contains methods used by Data providers that handle elements
 * with single items like select, radio and some more.
 */
abstract class AbstractItemProvider
{
    /**
     * Resolve "itemProcFunc" of elements.
     *
     * @param array $result Main result array
     * @param string $fieldName Field name to handle item list for
     * @param array $items Existing items array
     * @return array New list of item elements
     */
    protected function resolveItemProcessorFunction(array $result, $fieldName, array $items)
    {
        $table = $result['tableName'];
        $config = $result['processedTca']['columns'][$fieldName]['config'];

        $pageTsProcessorParameters = null;
        if (!empty($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['itemsProcFunc.'])) {
            $pageTsProcessorParameters = $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['itemsProcFunc.'];
        }
        $processorParameters = [
            // Function manipulates $items directly and return nothing
            'items' => &$items,
            'config' => $config,
            'TSconfig' => $pageTsProcessorParameters,
            'table' => $table,
            'row' => $result['databaseRow'],
            'field' => $fieldName,
        ];

        try {
            GeneralUtility::callUserFunction($config['itemsProcFunc'], $processorParameters, $this);
        } catch (\Exception $exception) {
            // The itemsProcFunc method may throw an exception, create a flash message if so
            $languageService = $this->getLanguageService();
            $fieldLabel = $fieldName;
            if (!empty($result['processedTca']['columns'][$fieldName]['label'])) {
                $fieldLabel = $languageService->sL($result['processedTca']['columns'][$fieldName]['label']);
            }
            $message = sprintf(
                $languageService->sL('LLL:EXT:lang/locallang_core.xlf:error.items_proc_func_error'),
                $fieldLabel,
                $exception->getMessage()
            );
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                '',
                FlashMessage::ERROR,
                true
            );
            /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        return $items;
    }

    /**
     * PageTsConfig addItems:
     *
     * TCEFORMS.aTable.aField[.types][.aType].addItems.aValue = aLabel,
     * with type specific options merged by pageTsConfig already
     *
     * @param array $result result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     */
    protected function addItemsFromPageTsConfig(array $result, $fieldName, array $items)
    {
        $table = $result['tableName'];
        if (!empty($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['addItems.'])
            && is_array($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['addItems.'])
        ) {
            $addItemsArray = $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['addItems.'];
            foreach ($addItemsArray as $value => $label) {
                // If the value ends with a dot, it is a subelement like "34.icon = mylabel.png", skip it
                if (substr($value, -1) === '.') {
                    continue;
                }
                // Check if value "34 = mylabel" also has a "34.icon = myImage.png"
                $icon = null;
                if (isset($addItemsArray[$value . '.'])
                    && is_array($addItemsArray[$value . '.'])
                    && !empty($addItemsArray[$value . '.']['icon'])
                ) {
                    $icon = $addItemsArray[$value . '.']['icon'];
                }
                $items[] = array($label, $value, $icon);
            }
        }
        return $items;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
