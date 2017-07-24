<?php
namespace TYPO3\CMS\Install\ViewHelpers\Uri;

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
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * A view helper for creating URIs to install tool actions.
 *
 * = Examples =
 *
 * <code title="URI to the show-action of the current controller">
 * <f:uri.action action="importantActions" />
 * </code>
 * <output>
 * install.php?install[action]=importantActions&amp;install[context]=
 * </output>
 */
class ActionViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     *
     * @api
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('action', 'string', 'Target action');
        $this->registerArgument('controller', 'string', 'Target controller.', false, 'tool');
        $this->registerArgument('arguments', 'array', 'Arguments', false, []);
        $this->registerArgument('section', 'string', 'The anchor to be added to the URI', false, '');
        $this->registerArgument('additionalParams', 'array', 'additional query parameters that won\'t be prefixed like $arguments (overrule $arguments)', false, []);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $action = $arguments['action'];

        if ($action === 'backend') {
            return GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'index.php';
        }
        if ($action === 'frontend') {
            return GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'index.php';
        }

        $section = $arguments['section'];
        $additionalParams = $arguments['additionalParams'];
        $controller = $arguments['controller'];
        $arguments = $arguments['arguments'];

        $arguments['action'] = $action;
        $arguments['controller'] = $controller;
        if (!empty(GeneralUtility::_GET('install')['context'])) {
            $arguments['context'] = GeneralUtility::_GET('install')['context'];
        }

        return GeneralUtility::getIndpEnv('TYPO3_REQUEST_SCRIPT')
            . '?'
            . GeneralUtility::implodeArrayForUrl('install', $arguments)
            . GeneralUtility::implodeArrayForUrl('', $additionalParams)
            . ($section ? '#' . $section : '');
    }
}
