<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Format;

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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Wrapper for PHPs nl2br function.
 * @see http://www.php.net/manual/en/function.nl2br.php
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:format.nl2br>{text_with_linebreaks}</f:format.nl2br>
 * </code>
 * <output>
 * text with line breaks replaced by <br />
 * </output>
 *
 * <code title="Inline notation">
 * {text_with_linebreaks -> f:format.nl2br()}
 * </code>
 * <output>
 * text with line breaks replaced by <br />
 * </output>
 */
class Nl2brViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'string', 'string to format');
    }

    /**
     * Applies nl2br() on the specified value.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return nl2br($renderChildrenClosure());
    }
}
