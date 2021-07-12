<?php

declare(strict_types=1);
namespace TYPO3\CMS\Styleguide\ViewHelpers;

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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Render code snippets in a usable way
 */
class CodeViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('language', 'string', 'the language identifier, e.g. html, php, etc.', true);
        $this->registerArgument('codeonly', 'bool', 'if true show only the code but not the example', false, false);
    }

    /**
     * @return mixed
     */
    public function render()
    {
        $content = $this->renderChildren();
        $_lines = explode("\n", $content);
        $lines = [];
        foreach ($_lines as $line) {
            $line = preg_replace('/(\s)/', ' ', $line);
            if (trim($line) !== '') {
                $lines[] = $line;
            }
        }
        $indentSize = strlen($lines[0]) - strlen(ltrim($lines[0]));
        $content = '';
        foreach ($lines as $line) {
            $tmp = substr($line, $indentSize) ?: '';
            $spaces = strlen($tmp) - strlen(ltrim($tmp));
            $content .= str_repeat('  ', $spaces) . ltrim($line) . chr(10);
        }

        $markup = [];
        if (!$this->arguments['codeonly']) {
            $markup[] = '<div class="example">';
            $markup[] = $content;
            $markup[] = '</div>';
        }
        $markup[] = '<div class="example code">';
        $markup[] = '<pre>';
        $markup[] = '<code class="language-' . htmlspecialchars($this->arguments['language']) . '">';
        $markup[] = htmlspecialchars($content);
        $markup[] = '</code>';
        $markup[] = '</pre>';
        $markup[] = '</div>';
        return implode('', $markup);
    }
}
