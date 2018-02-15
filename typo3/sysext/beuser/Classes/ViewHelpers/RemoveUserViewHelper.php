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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Renders 'Delete user' link
 *
 * @internal
 */
class RemoveUserViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('backendUser', BackendUser::class, 'Target backendUser to switch active session to', true);
    }

    /**
     * Renders the URL to remove a user.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $urlParameters = [
            'cmd[be_users][' . $arguments['backendUser']->getUid() . '][delete]' => 1,
            'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('tce_db', $urlParameters);
    }
}
