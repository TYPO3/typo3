<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

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

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * This ViewHelper generates a HTML dump of the tagged variable.
 *
 * Examples
 * ========
 *
 * Simple
 * ------
 *
 * ::
 *
 *    <f:debug>{testVariables.array}</f:debug>
 *
 * foobarbazfoo
 *
 * All Features
 * ------------
 *
 * ::
 *
 *    <f:debug title="My Title" maxDepth="5"
 *        blacklistedClassNames="{0:'Tx_BlogExample_Domain_Model_Administrator'}"
 *        blacklistedPropertyNames="{0:'posts'}"
 *        plainText="true" ansiColors="false"
 *        inline="true"
 *        >
 *            {blogs}
 *        </f:debug>
 *
 * [A HTML view of the var_dump]
 */
class DebugViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * This prevents double escaping as the output is encoded in DebuggerUtility::var_dump
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Output of this viewhelper is already escaped
     *
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
        $this->registerArgument('title', 'string', 'optional custom title for the debug output');
        $this->registerArgument('maxDepth', 'int', 'Sets the max recursion depth of the dump (defaults to 8). De- or increase the number according to your needs and memory limit.', false, 8);
        $this->registerArgument('plainText', 'bool', 'If TRUE, the dump is in plain text, if FALSE the debug output is in HTML format.', false, false);
        $this->registerArgument('ansiColors', 'bool', 'If TRUE, ANSI color codes is added to the plaintext output, if FALSE (default) the plaintext debug output not colored.', false, false);
        $this->registerArgument('inline', 'bool', 'if TRUE, the dump is rendered at the position of the <f:debug> tag. If FALSE (default), the dump is displayed at the top of the page.', false, false);
        $this->registerArgument('blacklistedClassNames', 'array', 'An array of class names (RegEx) to be filtered. Default is an array of some common class names.');
        $this->registerArgument('blacklistedPropertyNames', 'array', 'An array of property names and/or array keys (RegEx) to be filtered. Default is an array of some common property names.');
    }

    /**
     * A wrapper for \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump().
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return DebuggerUtility::var_dump($renderChildrenClosure(), $arguments['title'], $arguments['maxDepth'], (bool)$arguments['plainText'], (bool)$arguments['ansiColors'], (bool)$arguments['inline'], $arguments['blacklistedClassNames'], $arguments['blacklistedPropertyNames']);
    }
}
