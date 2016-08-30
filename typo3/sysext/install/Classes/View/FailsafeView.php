<?php
namespace TYPO3\CMS\Install\View;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A view with basically wraps the standalone view for normal conditions
 * and implements a renderAlertStatus message for alert conditions
 * which would also make the install tool to fail.
 */
class FailsafeView extends \TYPO3\CMS\Extbase\Mvc\View\AbstractView
{
    /**
     * @var string
     */
    protected $templatePathAndFileName;

    /**
     * @var string
     */
    protected $layoutRootPath;

    /**
     * @var string
     */
    protected $partialRootPath;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
    }

    /**
     * Hand over regular rendering to standalone view,
     * or render alert status
     *
     * @param bool $alert
     * @return string
     */
    public function render($alert = false)
    {
        if ($alert) {
            return $this->renderAlertStatus();
        }
        /** @var \TYPO3\CMS\Install\View\StandaloneView $realView */
        $realView = $this->objectManager->get(\TYPO3\CMS\Install\View\StandaloneView::class);
        $realView->assignMultiple($this->variables);
        $realView->setTemplatePathAndFilename($this->templatePathAndFileName);
        $realView->setLayoutRootPaths([$this->layoutRootPath]);
        $realView->setPartialRootPaths([$this->partialRootPath]);

        return $realView->render();
    }

    /**
     * In case an alert happens we fall back to a simple PHP template
     *
     * @return string
     */
    protected function renderAlertStatus()
    {
        $templatePath = preg_replace('#\.html$#', '.phtml', $this->templatePathAndFileName);
        ob_start();
        include $templatePath;
        $renderedTemplate = ob_get_contents();
        ob_end_clean();

        return $renderedTemplate;
    }

    /**
     * @param string $templatePathAndFileName
     */
    public function setTemplatePathAndFileName($templatePathAndFileName)
    {
        $this->templatePathAndFileName = $templatePathAndFileName;
    }

    /**
     * @param string $layoutRootPath
     */
    public function setLayoutRootPath($layoutRootPath)
    {
        $this->layoutRootPath = $layoutRootPath;
    }

    /**
     * @param string $partialRootPath
     */
    public function setPartialRootPath($partialRootPath)
    {
        $this->partialRootPath = $partialRootPath;
    }
}
