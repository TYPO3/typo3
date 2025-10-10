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

namespace TYPO3\CMS\Fluid\ViewHelpers\Page;

use TYPO3\CMS\Core\PageTitle\RecordTitleProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to set the page title from Fluid templates.
 *
 * ```
 *    <f:page.title>My Custom Page Title</f:page.title>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-page-title
 */
final class TitleViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly RecordTitleProvider $pageTitleProvider) {}

    public function render(): string
    {
        $title = $this->renderChildren();
        if ($title !== null) {
            $this->pageTitleProvider->setTitle((string)$title);
        }
        return '';
    }
}
