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

namespace TYPO3\CMS\Backend\Tests\Functional\Form\Fixtures\TcaSelectItems;

use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorInterface;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;

class ItemsProcessorForTestingExceptionsForSelectItems implements ItemsProcessorInterface
{
    public function processItems(
        SelectItemCollection $items,
        ItemsProcessorContext $context,
    ): SelectItemCollection {
        $items->rewind();
        if (
            $items->current()->getLabel() !== 'aLabel'
            || $items->current()->getValue() !== 'aValue'
            || $context->fieldConfiguration['aKey'] !== 'aValue'
            || $context->fieldTSconfig !== ['itemParamKey' => 'itemParamValue']
            || $context->table !== 'aTable'
            || $context->row !== ['aField' => 'aValue']
            || $context->field !== 'aField'
            || $context->processorParameters !== ['hello' => 'world']
            || $context->additionalParameters['inlineParentUid'] !== 1
            || $context->additionalParameters['inlineParentTableName'] !== 'aTable'
            || $context->additionalParameters['inlineParentFieldName'] !== 'aField'
            || $context->additionalParameters['inlineParentConfig'] !== ['config' => 'someValue']
            || $context->additionalParameters['inlineTopMostParentUid'] !== 1
            || $context->additionalParameters['inlineTopMostParentTableName'] !== 'topMostTable'
            || $context->additionalParameters['inlineTopMostParentFieldName'] !== 'topMostField'
        ) {
            throw new \UnexpectedValueException('broken', 1761748822);
        }
        return $items;
    }
}
