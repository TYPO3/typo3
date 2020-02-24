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
 * The AbstractCtaButtonWidget class is the basic widget class for simple CTA widgets.
 * It is possible to extend this class for custom widgets.
 * Simply overwrite $this->link and $this->label to make use of this widget type.
 */
abstract class AbstractCtaButtonWidget extends AbstractWidget
{
    /**
     * This link will be the main data in the widget
     *
     * @var string
     */
    protected $link = '';

    /**
     * When filled, this is used as the button label
     *
     * @var string
     */
    protected $label = '';

    /**
     * When filled, a text is shown above the link
     *
     * @var string
     */
    protected $text = '';

    /**
     * This property contains the identifier of the icon that should be shown in the widget
     *
     * @var string
     */
    protected $icon;

    /**
     * @inheritDoc
     */
    protected $iconIdentifier = 'content-widget-calltoaction';

    /**
     * @inheritDoc
     */
    protected $templateName = 'CtaWidget';

    public function __construct(string $identifier)
    {
        parent::__construct($identifier);
        $this->height = 1;

        $this->view->assignMultiple([
            'link' => $this->link,
            'label' => $this->label,
            'icon' => $this->icon,
            'text' => $this->getLanguageService()->sL($this->text)
        ]);
    }
}
