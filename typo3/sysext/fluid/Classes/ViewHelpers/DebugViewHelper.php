<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Fluid\ViewHelpers;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

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
 *    <f:debug>{myVariable}</f:debug>
 *
 * [A HTML dump of myVariable value]
 *
 * All Features
 * ------------
 *
 * ::
 *
 *    <f:debug title="My Title" maxDepth="5"
 *        blacklistedClassNames="{0:'ACME\BlogExample\Domain\Model\Administrator'}"
 *        blacklistedPropertyNames="{0:'posts'}"
 *        plainText="true" ansiColors="false"
 *        inline="true"
*     >
 *        {blogs}
 *    </f:debug>
 *
 * [A HTML view of the var_dump]
 */
final class DebugViewHelper extends AbstractViewHelper
{
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

    public function initializeArguments(): void
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
     */
    public function render(): string
    {
        return DebuggerUtility::var_dump(
            $this->renderChildren(),
            is_scalar($this->arguments['title']) ? (string)$this->arguments['title'] : null,
            (int)$this->arguments['maxDepth'],
            (bool)$this->arguments['plainText'],
            (bool)$this->arguments['ansiColors'],
            (bool)$this->arguments['inline'],
            is_array($this->arguments['blacklistedClassNames']) ? $this->arguments['blacklistedClassNames'] : null,
            is_array($this->arguments['blacklistedPropertyNames']) ? $this->arguments['blacklistedPropertyNames'] : null
        );
    }
}
