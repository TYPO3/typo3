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

namespace TYPO3\CMS\Dashboard\Widgets;

use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Concrete CTA button implementation
 *
 * Shows a widget with a CTA button to easily go to a specific page or do a specific action. You can add a button to the
 * widget by defining a button provider.
 *
 * The following options are available during registration:
 * - text           string          Adds a text to the widget to give some more background information about
 *                                  what a user can expect when clicking the button. You can either enter a
 *                                  normal string or a translation string (eg. LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.documentation.gettingStarted.text)
 * @see ButtonProviderInterface
 */
class CtaWidget implements WidgetInterface
{
    /**
     * @var WidgetConfigurationInterface
     */
    private $configuration;

    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var array
     */
    private $options;

    /**
     * @var ButtonProviderInterface|null
     */
    private $buttonProvider;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        StandaloneView $view,
        $buttonProvider = null,
        array $options = []
    ) {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->options =  array_merge(['text' => ''], $options);
        $this->buttonProvider = $buttonProvider;
    }

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('Widget/CtaWidget');
        $this->view->assignMultiple([
            'text' => $this->options['text'],
            'options' => $this->options,
            'button' => $this->buttonProvider,
            'configuration' => $this->configuration,
        ]);
        return $this->view->render();
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
