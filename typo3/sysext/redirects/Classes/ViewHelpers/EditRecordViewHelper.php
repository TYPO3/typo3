<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Redirects\ViewHelpers;

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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Edit Record ViewHelper
 * @internal
 * @todo remove once general edit view helper exists
 */
class EditRecordViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('command', 'string', 'New, Edit or Remove a Record.', true);
        $this->registerArgument('uid', 'int', 'UID of the Record to edit.', true);
    }

    /**
     * Render link
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        switch ($arguments['command']) {
            case 'delete':
                $urlParameters = [
                    'cmd[sys_redirect][' . $arguments['uid'] . '][delete]' => 1,
                    'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ];
                $route = 'tce_db';
                break;
            case 'unhide':
                $urlParameters = [
                    'data[sys_redirect][' . $arguments['uid'] . '][disabled]' => 0,
                    'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ];
                $route = 'tce_db';
                break;
            case 'hide':
                $urlParameters = [
                    'data[sys_redirect][' . $arguments['uid'] . '][disabled]' => 1,
                    'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ];
                $route= 'tce_db';
                break;
            case 'resetcounter':
                $urlParameters = [
                    'data[sys_redirect][' . $arguments['uid'] . '][hitcount]' => 0,
                    'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ];
                $route = 'tce_db';
                break;
            default:
                throw new \InvalidArgumentException('Invalid command given to EditRecordViewhelper.', 1516708789);
        }
        return (string)$uriBuilder->buildUriFromRoute($route, $urlParameters);
    }
}
