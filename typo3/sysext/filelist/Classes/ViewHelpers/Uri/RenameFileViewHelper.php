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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * @internal
 */
class RenameFileViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('file', \TYPO3\CMS\Core\Resource\AbstractFile::class, '', true);
        $this->registerArgument('returnUrl', 'string', '', false, '');
    }

    /**
     * Renders a link to rename a file
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if (empty($arguments['returnUrl'])) {
            $arguments['returnUrl'] = GeneralUtility::getIndpEnv('REQUEST_URI');
        }

        /** @var \TYPO3\CMS\Core\Resource\AbstractFile $file */
        $file = $arguments['file'];

        $params = [
            'target' => $file->getCombinedIdentifier(),
            'returnUrl' => $arguments['returnUrl']
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('file_rename', $params);
    }
}
