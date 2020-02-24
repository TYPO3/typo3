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
 * It is possible to extend this class for custom widgets.
 * In your class you have to set $this->items with the data to display.
 */
abstract class AbstractListWidget extends AbstractWidget
{
    /**
     * This property should contain the items to be displayed
     *
     * @var array
     */
    protected $items = [];

    /**
     * Limit the amount of items to be displayed
     *
     * @var int
     */
    protected $limit = 5;

    /**
     * Link to e.g. an external resource with the full items list
     *
     * @var string
     */
    protected $moreItemsLink = '';

    /**
     * This should be the text for the more items link
     *
     * @var string
     */
    protected $moreItemsText = '';

    /**
     * @inheritDoc
     */
    protected $height = 4;

    /**
     * @inheritDoc
     */
    protected $iconIdentifier = 'content-widget-list';

    /**
     * @inheritDoc
     */
    protected $templateName = 'ListWidget';

    public function renderWidgetContent(): string
    {
        $this->view->assign('items', $this->items);
        $this->view->assign('moreItemsLink', $this->moreItemsLink);
        $this->view->assign('moreItemsText', $this->moreItemsText);
        return $this->view->render();
    }
}
