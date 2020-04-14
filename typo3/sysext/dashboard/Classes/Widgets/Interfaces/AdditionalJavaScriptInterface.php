<?php

declare(strict_types=1);
namespace TYPO3\CMS\Dashboard\Widgets\Interfaces;

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

/**
 * In case a widget should provide additional JavaScript files, the widget must implement this interface.
 */
interface AdditionalJavaScriptInterface
{
    /**
     * This method returns an array with paths to required JS files.
     * e.g. ['EXT:myext/Resources/Public/JavaScript/my_widget.js']
     *
     * @return array
     */
    public function getJsFiles(): array;
}
