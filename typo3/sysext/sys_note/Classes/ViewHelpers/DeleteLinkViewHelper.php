<?php
namespace TYPO3\CMS\SysNote\ViewHelpers;

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
 * ViewHelper to create a link to delete a note
 *
 * @internal
 */
class DeleteLinkViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('id', 'int', 'uid of the note', true);
    }

    /**
     * Create link to delete a note
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
            'cmd[sys_note][' . $arguments['id'] . '][delete]' => 1,
            'redirect' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('tce_db', $urlParameters);
    }
}
