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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider\Fixtures;

use TYPO3\CMS\Core\DataHandling\ItemsProcessorContext;
use TYPO3\CMS\Core\DataHandling\ItemsProcessorInterface;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\Struct\SelectItemCollection;

/**
 * This class is used for testing itemsProcessors in the context of check, radio-type fields.
 * Strictly speaking it should differentiate the type (somehow), but this is not important
 * as we are not testing the behaviour of SelectItem but the general behaviour of adding items with itemsProcessors.
 */
class ItemsProcessor2 implements ItemsProcessorInterface
{
    public function processItems(
        SelectItemCollection $items,
        ItemsProcessorContext $context,
    ): SelectItemCollection {
        $items->add(new SelectItem('select', 'label2', 'value2'));
        return $items;
    }
}
