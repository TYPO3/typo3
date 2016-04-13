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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Display image magick commands
 *
 * @internal
 */
class ImageMagickCommandsViewHelper extends AbstractViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Display image magick commands
     *
     * @param array $commands Given commands
     * @return string Formatted commands
     */
    public function render(array $commands = array())
    {
        return static::renderStatic(
            array(
                'commands' => $commands,
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
        $commands = $arguments['commands'];

        $result = array();
        foreach ($commands as $commandGroup) {
            $result[] = '<strong>Command:</strong>' . LF . htmlspecialchars($commandGroup[1]);
            // If 3 elements: last one is result
            if (count($commandGroup) === 3) {
                $result[] = '<strong>Result:</strong>' . LF . htmlspecialchars($commandGroup[2]);
            }
        }
        return '<pre><code class="language-bash">' . implode(LF, $result) . '</code></pre>';
    }
}
