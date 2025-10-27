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

namespace TYPO3\CMS\Styleguide\UserFunctions\FormEngine;

use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorInterface;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;

class TypeCheckbox8ItemsProcessor implements ItemsProcessorInterface
{
    public function processItems(
        SelectItemCollection $items,
        ItemsProcessorContext $context,
    ): SelectItemCollection {
        $label = sprintf(
            'item 3 from itemsProcessors with parameter extra = %s',
            $context->processorParameters['extra']
        );
        $items->add(
            new SelectItem(
                type: $context->fieldConfiguration['type'],
                label: $label,
                value: ''
            )
        );
        return $items;
    }
}
