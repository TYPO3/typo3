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
 * The AbstractListWidget class is the basic widget class for structured content.
 * Is it possible to extends this class for own widgets.
 * In your class you have to set $this->items with the data to display.
 */
abstract class AbstractListWidget extends AbstractWidget
{
    protected $items = [];
    protected $iconIdentifier = 'dashboard-bars';
    protected $limit = 5;
    protected $totalItems = 0;
    protected $templateName = 'ListWidget';
    protected $height = 4;
    protected $width = 2;
    protected $moreItemsLink = '';
    protected $moreItemsText = '';

    public function renderWidgetContent(): string
    {
        $this->view->assign('items', $this->items);
        $this->view->assign('moreItemsLink', $this->moreItemsLink);
        $this->view->assign('moreItemsText', $this->moreItemsText);
        $this->view->assign('totalNumberOfItems', $this->totalItems);
        return $this->view->render();
    }
}
