<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Returns a string meant to be used as css class stating whether an extension is
 * available or installed
 * @internal
 */
class InstallationStateCssClassViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('needle', 'string', '', true);
        $this->registerArgument('haystack', 'array', '', true);
    }

    /**
     * Returns string meant to be used as css class
     * 'installed' => if an extension is installed
     * 'available' => if an extension is available in the system
     * '' (empty string) => if neither installed nor available
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string the rendered a tag
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $needle = $arguments['needle'];
        $haystack = $arguments['haystack'];
        if (array_key_exists($needle, $haystack)) {
            if (isset($haystack[$needle]['installed']) && $haystack[$needle]['installed'] === true) {
                return 'installed';
            }
            return 'available';
        }
        return '';
    }
}
