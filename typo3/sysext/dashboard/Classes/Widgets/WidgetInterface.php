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

/**
 * The WidgetInterface is the base interface for all kind of widgets.
 * All widgets must implement this interface.
 * It contains the methods which are required for all widgets.
 */
interface WidgetInterface
{
    /**
     * This method returns the content of a widget. The returned markup will be delivered
     * by an AJAX call and will not be escaped.
     * Be aware of XSS and ensure that the content is well encoded.
     *
     * @return string
     */
    public function renderWidgetContent(): string;

    /**
     * This method returns the options of the widget as set in the registration.
     *
     * As we won't add new breaking changes to v11 at this moment, this method is commented for now. In v12 this will
     * be part of the interface.
     * @return array
     */
    /**
     * public function getOptions(): array;
     */
}
