<?php
namespace TYPO3\CMS\Scheduler\ViewHelpers;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Create internal link within backend app
 * @internal
 */
class ModuleLinkViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Render module link with command and arguments
     *
     * @param string $controller The "controller" of scheduler. Possible values are "scheduler", "check", "info"
     * @param string $action The action to be called within each controller
     * @param array $arguments Arguments for the action
     * @return string
     */
    public function render($controller, $action, array $arguments = [])
    {
        return static::renderStatic(
            [
                'controller' => $controller,
                'action' => $action,
                'arguments' => $arguments,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $moduleArguments = [];
        $moduleArguments['SET']['function'] = $arguments['controller'];
        $moduleArguments['CMD'] = $arguments['action'];
        if (!empty($arguments['arguments'])) {
            $moduleArguments['tx_scheduler'] = $arguments['arguments'];
        }

        return BackendUtility::getModuleUrl('system_txschedulerM1', $moduleArguments);
    }
}
