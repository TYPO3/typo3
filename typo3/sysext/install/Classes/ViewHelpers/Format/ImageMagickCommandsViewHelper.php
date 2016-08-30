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
 * Display image magick commands
 *
 * @internal
 */
class ImageMagickCommandsViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Display image magick commands
     *
     * @param array $commands Given commands
     * @return string Formatted commands
     */
    public function render(array $commands = [])
    {
        return static::renderStatic(
            [
                'commands' => $commands,
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
        $commands = $arguments['commands'];

        $result = [];
        foreach ($commands as $commandGroup) {
            $result[] = 'Command: ' . $commandGroup[1];
            // If 3 elements: last one is result
            if (count($commandGroup) === 3) {
                $result[] = 'Result: ' . $commandGroup[2];
            }
        }
        return '<textarea class="form-control" rows="' . count($result) . '" cols="50">' . implode(LF, $result) . '</textarea>';
    }
}
