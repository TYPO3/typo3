<?php
namespace TYPO3\CMS\Styleguide\ViewHelpers\Be\Buttons;

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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;

/**
 * Wrapper for f:be.buttons.shortcut
 * Adapts HTML for 7.6 ModuleTemplate API
 */
class ShortcutViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\Buttons\ShortcutViewHelper
{

    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $getVars = $arguments['getVars'];
        $setVars = $arguments['setVars'];

        $mayMakeShortcut = $GLOBALS['BE_USER']->mayMakeShortcut();

        if ($mayMakeShortcut) {
            $doc = GeneralUtility::makeInstance(DocumentTemplate::class);
            $currentRequest = $renderingContext->getControllerContext()->getRequest();
            $extensionName = $currentRequest->getControllerExtensionName();
            $moduleName = $currentRequest->getPluginName();
            if (count($getVars) === 0) {
                $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
                $getVars = array('id', 'M', $modulePrefix);
            }
            $getList = implode(',', $getVars);
            $setList = implode(',', $setVars);
            return $doc->makeShortcutIcon($getList, $setList, $moduleName, '', 'btn btn-default btn-sm');
        }
        return '';
    }
}
