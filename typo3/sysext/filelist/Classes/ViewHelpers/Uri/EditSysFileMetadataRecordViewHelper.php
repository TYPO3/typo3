<?php
namespace TYPO3\CMS\Filelist\ViewHelpers\Uri;

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

use Closure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Class EditSysFileMetadataRecordViewHelper
 */
class EditSysFileMetadataRecordViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Renders a link to edit sys_file_metadata
     *
     * @param int $uid
     * @param string $returnUrl
     *
     * @return string
     */
    public function render($uid, $returnUrl = '')
    {
        return static::renderStatic(
            [
                'uid' => $uid,
                'returnUrl' => $returnUrl,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Renders a link to edit sys_file_metadata
     *
     * @param array $arguments
     * @param Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if (empty($arguments['returnUrl'])) {
            $arguments['returnUrl'] = GeneralUtility::getIndpEnv('REQUEST_URI');
        }

        $params = [
            'edit' => ['sys_file_metadata' => [$arguments['uid'] => 'edit']],
            'returnUrl' => $arguments['returnUrl']
        ];

        return BackendUtility::getModuleUrl('record_edit', $params);
    }
}
