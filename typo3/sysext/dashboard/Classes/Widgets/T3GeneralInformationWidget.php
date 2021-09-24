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

use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Concrete TYPO3 information widget
 *
 * This widget will give some general information about TYPO3 version and the version installed.
 *
 * There are no options available for this widget
 */
class T3GeneralInformationWidget implements WidgetInterface
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

    public function __construct(
        WidgetConfigurationInterface $configuration,
        StandaloneView $view,
        array $options = []
    ) {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->options = $options;
    }

    public function renderWidgetContent(): string
    {
        $typo3Information = new Typo3Information();
        $typo3Version = new Typo3Version();

        $this->view->setTemplate('Widget/T3GeneralInformationWidget');
        $this->view->assignMultiple([
            'title' => 'TYPO3 CMS ' . $typo3Version->getVersion(),
            'copyrightYear' => $typo3Information->getCopyrightYear(),
            'currentVersion' => $typo3Version->getVersion(),
            'donationUrl' => $typo3Information::URL_DONATE,
            'copyRightNotice' => $typo3Information->getCopyrightNotice(),
            'options' => $this->options,
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
