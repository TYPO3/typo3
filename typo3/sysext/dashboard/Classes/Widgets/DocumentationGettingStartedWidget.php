<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Dashboard\Widgets;

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
 * This widget will show a link to the documentation of TYPO3 to make it easier for people
 * to find the right documentation.
 */
class DocumentationGettingStartedWidget extends AbstractCtaButtonWidget
{
    protected $title = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.documentation.gettingStarted.title';
    protected $text = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.documentation.gettingStarted.text';
    protected $description = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.documentation.gettingStarted.description';
    protected $label = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.documentation.gettingStarted.content.label';
    protected $link = 'https://docs.typo3.org/m/typo3/tutorial-getting-started/master/en-us/Index.html';
    protected $iconIdentifier = 'content-widget-text';
}
