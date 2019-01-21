<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Controller;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\View\TemplatePaths;
use TYPO3Fluid\Fluid\View\TemplateView;

/**
 * A class representing error messages shown on a page, rendered via fluid.
 * Classic Example: "No pages are found on rootlevel"
 */
class ErrorPageController
{
    /**
     * The view object
     * @var TemplateView
     */
    protected $view;

    /**
     * The severity level
     * @var int
     */
    protected $severity = AbstractMessage::ERROR;

    /**
     * Sets up the view
     */
    public function __construct()
    {
        $this->view = GeneralUtility::makeInstance(TemplateView::class);
        $context = new RenderingContext($this->view);
        $context->setControllerName('ErrorPage');
        $context->setTemplatePaths(new TemplatePaths([
            'templateRootPaths' => [
                Environment::getFrameworkBasePath() . '/core/Resources/Private/Templates/ErrorPage/'
            ]
        ]));
        $this->view->setRenderingContext($context);
    }

    /**
     * Renders the view and returns the content
     *
     * @param string $title The title to be shown
     * @param string $message The message to be shown
     * @param int $severity The severity of the error, see AbstractMessage constants
     * @param int $errorCode The error code to be referenced
     * @return string the output of the view
     */
    public function errorAction(
        string $title,
        string $message,
        int $severity = AbstractMessage::ERROR,
        int $errorCode = 0
    ): string {
        $this->severity = $severity;
        $classes = [
            AbstractMessage::NOTICE => 'notice',
            AbstractMessage::INFO => 'information',
            AbstractMessage::OK => 'ok',
            AbstractMessage::WARNING => 'warning',
            AbstractMessage::ERROR => 'error'
        ];
        $this->view->assign('severityCssClass', $classes[$this->severity]);
        $this->view->assign('severity', $this->severity);
        $this->view->assign('message', $message);
        $this->view->assign('title', $title);
        $this->view->assign('errorCodeUrlPrefix', TYPO3_URL_EXCEPTION);
        $this->view->assign('errorCode', $errorCode);
        $this->view->assign('logo', PathUtility::getAbsoluteWebPath(Environment::getFrameworkBasePath() . '/backend/Resources/Public/Images/typo3_orange.svg'));
        $this->view->assign('cssFile', PathUtility::getAbsoluteWebPath(Environment::getFrameworkBasePath() . '/core/Resources/Public/Css/errorpage.css'));
        $this->view->assign('copyrightYear', TYPO3_copyright_year);
        return $this->view->render('Error');
    }
}
