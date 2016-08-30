<?php
namespace TYPO3\CMS\Install\ViewHelpers\Format;

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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Simplified crop view helper that does not need a frontend environment
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.crop maxCharacters="10">This is some very long text</f:format.crop>
 * </code>
 * <output>
 * This is...
 * </output>
 *
 * <code title="Inline notation">
 * {someLongText -> f:format.crop(maxCharacters: 10)}
 * </code>
 * <output>
 * someLongText cropped after 10 characters...
 * (depending on the value of {someLongText})
 * </output>
 *
 * @internal
 */
class CropViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Render the cropped text
     *
     * @param int $maxCharacters Place where to truncate the string
     * @throws \TYPO3\CMS\Install\ViewHelpers\Exception
     * @return string cropped text
     */
    public function render($maxCharacters)
    {
        return static::renderStatic(
            [
                'maxCharacters' => $maxCharacters,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @throws \TYPO3\CMS\Install\ViewHelpers\Exception
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $maxCharacters = $arguments['maxCharacters'];
        if (empty($maxCharacters) || $maxCharacters < 1) {
            throw new \TYPO3\CMS\Install\ViewHelpers\Exception(
                'maxCharacters must be a positive integer',
                1371410113
            );
        }
        $stringToTruncate = $renderChildrenClosure();
        return substr($stringToTruncate, 0, $maxCharacters);
    }
}
