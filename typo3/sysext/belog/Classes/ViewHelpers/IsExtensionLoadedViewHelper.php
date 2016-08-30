<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Returns true, if a specific extension is loaded
 * @internal
 */
class IsExtensionLoadedViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Checks whether an extension is loaded.
     *
     * @param string $extensionKey The extension's key
     * @return bool TRUE if extension is loaded, FALSE otherwise
     */
    public function render($extensionKey)
    {
        return static::renderStatic(
            [
                'extensionKey' => $extensionKey
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
        return ExtensionManagementUtility::isLoaded($arguments['extensionKey']);
    }
}
