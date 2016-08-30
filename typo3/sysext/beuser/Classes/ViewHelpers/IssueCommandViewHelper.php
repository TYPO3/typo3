<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Render a link to DataHandler command
 * @internal
 */
class IssueCommandViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Returns a URL with a command to TYPO3 Core Engine (tce_db.php)
     *
     * @param string $parameters Is a set of GET params to send to tce_db.php. Example: "&cmd[tt_content][123][move]=456" or "&data[tt_content][123][hidden]=1&data[tt_content][123][title]=Hello%20World
     * @param string $redirectUrl Redirect URL if any other that \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI') is wished
     *
     * @return string URL to tce_db.php + parameters
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::getLinkToDataHandlerAction()
     */
    public function render($parameters, $redirectUrl = '')
    {
        return static::renderStatic(
            [
                'parameters' => $parameters,
                'redirectUrl' => $redirectUrl
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
        /** @var BackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];
        $urlParameters = [
            'vC' => $beUser->veriCode(),
            'prErr' => 1,
            'uPT' => 1,
            'redirect' => $arguments['redirectUrl'] ?: GeneralUtility::getIndpEnv('REQUEST_URI')
        ];
        if (isset($arguments['parameters'])) {
            $parametersArray = GeneralUtility::explodeUrl2Array($arguments['parameters']);
            $urlParameters += $parametersArray;
        }
        return htmlspecialchars(BackendUtility::getModuleUrl('tce_db', $urlParameters));
    }
}
