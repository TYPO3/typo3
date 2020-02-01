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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\Interfaces\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * The AbstractWidget class is the basic widget class for all widgets
 * It is possible to extends this class for own widgets, but EXT:dashboard provides
 * some more specific types of widgets to extend from. For more details, please check:
 * @see AbstractCtaButtonWidget
 * @see AbstractListWidget
 * @see AbstractRssWidget
 */
abstract class AbstractWidget implements WidgetInterface
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var int
     */
    protected $height = 2;

    /**
     * @var int
     */
    protected $width = 2;

    /**
     * @var string
     */
    protected $iconIdentifier = '';

    /**
     * @var string
     */
    protected $templateName = 'Widget';

    /**
     * @var ViewInterface
     */
    protected $view;

    protected $additionalClasses = '';

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;

        $this->initializeView();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getTitle(): string
    {
        return $this->getLanguageService()->sL($this->title) ?: $this->title;
    }

    public function getDescription(): string
    {
        return $this->getLanguageService()->sL($this->description) ?: $this->description;
    }

    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function renderWidgetContent(): string
    {
        return $this->view->render();
    }

    public function getAdditionalClasses(): string
    {
        return $this->additionalClasses;
    }

    protected function initializeView(): void
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplate('Widget/' . $this->templateName);

        $dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? '%m-%d-%Y' : '%d-%m-%Y';
        $this->view->assign('dateFormat', $dateFormat);

        $this->view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName('dashboard');

        $this->view->assign('title', $this->getTitle());
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
