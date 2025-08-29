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

namespace TYPO3\CMS\Frontend\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\EventListener\RemoveListTypePluginFromNewContentElementWizard;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RemoveListTypePluginFromNewContentElementWizardTest extends UnitTestCase
{
    #[Test]
    public function pluginListTypeRemovedIfThereAreNoItems(): void
    {
        $wizardItems = [
            'header' => [],
            'header_item1' => [],
            'plugins' => [],
            'plugins_list' => [],
            'forms' => [],
            'forms_item1' => [],
        ];
        $event = new ModifyNewContentElementWizardItemsEvent($wizardItems, [], 0, 0, 0, new ServerRequest());
        $eventListener = new RemoveListTypePluginFromNewContentElementWizard();
        $expected = [
            'header' => [],
            'header_item1' => [],
            'plugins' => [],
            'forms' => [],
            'forms_item1' => [],
        ];
        $eventListener($event);
        $result = $event->getWizardItems();
        self::assertSame($expected, $result);
    }

    #[Test]
    public function pluginListTypeNotRemovedIfThereAreItems(): void
    {
        $GLOBALS['TCA']['tt_content']['types']['list']['subtype_value_field'] = 'list_type';
        $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] = [
            ['label' => 'foo', 'value' => 'foo'],
        ];
        $wizardItems = [
            'header' => [],
            'header_item1' => [],
            'plugins' => [],
            'plugins_list' => [],
            'plugins_some_plugin' => [],
            'forms' => [],
            'forms_item1' => [],
        ];
        $event = new ModifyNewContentElementWizardItemsEvent($wizardItems, [], 0, 0, 0, new ServerRequest());
        $eventListener = new RemoveListTypePluginFromNewContentElementWizard();
        $expected = [
            'header' => [],
            'header_item1' => [],
            'plugins' => [],
            'plugins_list' => [],
            'plugins_some_plugin' => [],
            'forms' => [],
            'forms_item1' => [],
        ];
        $eventListener($event);
        $result = $event->getWizardItems();
        self::assertSame($expected, $result);
    }
}
