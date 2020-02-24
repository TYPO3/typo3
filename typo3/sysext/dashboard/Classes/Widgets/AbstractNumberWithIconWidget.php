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
 * The AbstractNumberWithIconWidget class is the basic widget class to display a number next to an icon.
 * It is possible to extend this class for own widgets.
 * Simply overwrite $this->subtitle, $this->number and $this->icon to make use of this widget type.
 */
abstract class AbstractNumberWithIconWidget extends AbstractWidget
{
    /**
     * When filled, a subtitle is shown below the title
     *
     * @var string
     */
    protected $subtitle;

    /**
     * This number will be the main data in the widget
     *
     * @var int
     */
    protected $number;

    /**
     * This property contains the identifier of the icon that should be shown in the widget
     *
     * @var string
     */
    protected $icon;

    /**
     * @inheritDoc
     */
    protected $iconIdentifier = 'content-widget-number';

    /**
     * @inheritDoc
     */
    protected $templateName = 'NumberWithIconWidget';

    protected function initializeView(): void
    {
        parent::initializeView();
        $this->view->assign('icon', $this->icon);
        $this->view->assign('subtitle', $this->getSubTitle());
        $this->view->assign('number', $this->number);
    }

    public function getSubTitle(): string
    {
        return $this->getLanguageService()->sL($this->subtitle) ?: $this->subtitle;
    }
}
