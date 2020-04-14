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

namespace TYPO3\CMS\Backend\ViewHelpers;

use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\LanguageColumn;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class LanguageColumnViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        $this->registerArgument('languageColumn', LanguageColumn::class, 'Language column object which is context for column', true);
        $this->registerArgument('columnNumber', 'int', 'Number (colPos) of column within LanguageColumn to be returned', true);
    }

    public function render(): GridColumn
    {
        return $this->arguments['languageColumn']->getGrid()->getColumns()[$this->arguments['columnNumber']];
    }
}
