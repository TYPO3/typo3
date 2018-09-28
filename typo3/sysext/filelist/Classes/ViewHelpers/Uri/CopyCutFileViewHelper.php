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
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Class EditFileContentViewHelper
 */
class CopyCutFileViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('file', \TYPO3\CMS\Core\Resource\AbstractFile::class, '', true);
        $this->registerArgument('copyOrCut', 'string', '', false, 'copy');
    }

    /**
     * Renders a link to copy a file
     *
     * @param array $arguments
     * @param Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function renderStatic(array $arguments, Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if ($arguments['copyOrCut'] !== 'cut' && $arguments['copyOrCut'] !== 'copy') {
            throw new \InvalidArgumentException('Argument "copyOrCut" must be either "copy" or "cut"', 1540548015);
        }

        /** @var \TYPO3\CMS\Core\Resource\AbstractFile $file */
        $file = $arguments['file'];

        /** @var Clipboard $clipboard */
        $clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $clipboard->initializeClipboard();

        $fullIdentifier = $file->getCombinedIdentifier();
        $md5 = GeneralUtility::shortMD5($fullIdentifier);
        $isSel = $clipboard->isSelected('_FILE', $md5);

        if ($arguments['copyOrCut'] === 'copy') {
            return $clipboard->selUrlFile($fullIdentifier, true, $isSel === 'copy');
        }
        return $clipboard->selUrlFile($fullIdentifier, false, $isSel === 'cut');
    }
}
