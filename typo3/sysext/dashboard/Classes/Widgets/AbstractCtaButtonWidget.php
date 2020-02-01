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
 * Is it possible to extends this class for own widgets.
 * Simply overwrite $this->link and $this->label to make use of this widget type.
 */
abstract class AbstractCtaButtonWidget extends AbstractWidget
{
    protected $link = '';
    protected $label = '';
    protected $text = '';
    protected $icon;
    protected $iconIdentifier = 'dashboard-cta';
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
