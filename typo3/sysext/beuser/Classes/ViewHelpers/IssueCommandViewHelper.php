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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Render a link to DataHandler command
 * @internal
 */
class IssueCommandViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('parameters', 'string', 'Is a set of GET params to send to route tce_db (SimpleDataHandlerController). Example: "&cmd[tt_content][123][move]=456" or "&data[tt_content][123][hidden]=1&data[tt_content][123][title]=Hello%20World', true);
        $this->registerArgument('redirectUrl', 'string', 'Redirect URL if any other that \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv(\'REQUEST_URI\') is preferred', false, '');
    }

    /**
     * Returns a URL with a command to TYPO3 Core Engine - DataHandler (route tce_db)
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string URL to tce_db + parameters
     * @see \TYPO3\CMS\Backend\Utility\BackendUtility::getLinkToDataHandlerAction()
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $urlParameters = [
            'redirect' => $arguments['redirectUrl'] ?: GeneralUtility::getIndpEnv('REQUEST_URI')
        ];
        if (isset($arguments['parameters'])) {
            $parametersArray = GeneralUtility::explodeUrl2Array($arguments['parameters']);
            $urlParameters += $parametersArray;
        }
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('tce_db', $urlParameters);
    }
}
