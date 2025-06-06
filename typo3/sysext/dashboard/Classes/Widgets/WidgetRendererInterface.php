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

namespace TYPO3\CMS\Dashboard\Widgets;

use TYPO3\CMS\Core\Settings\SettingDefinition;

/**
 * The WidgetRendererInterface is the (new) base interface for all kind of widgets.
 * All widgets should implement this interface. (but can also implement WidgetInterface for the time being)
 * It contains the methods which are required for all widgets.
 */
interface WidgetRendererInterface
{
    /**
     * This method returns the content of a widget. The returned markup will be delivered
     * by an AJAX call and will not be escaped.
     * Be aware of XSS and ensure that the content is well encoded.
     */
    public function renderWidget(WidgetContext $context): WidgetResult;

    /**
     * @return SettingDefinition[]
     */
    public function getSettingsDefinitions(): array;
}
