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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * ViewHelper to create a link to delete a note
 *
 * @internal
 */
class DeleteLinkViewHelper extends AbstractViewHelper
{
    /**
     * Create link to delete a note
     *
     * @param int $id uid of the note
     * @return string link
     */
    public function render($id)
    {
        return static::renderStatic(
            array(
                'id' => $id
            ),
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
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
        return BackendUtility::getModuleUrl('tce_db', $urlParameters);
    }
}
