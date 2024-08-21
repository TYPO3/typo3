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

namespace TYPO3\CMS\Styleguide\ViewHelpers;

use TYPO3\CMS\Backend\CodeEditor\CodeEditor;
use TYPO3\CMS\Backend\CodeEditor\Registry\ModeRegistry;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for rendering a code example
 *
 * Examples
 * ========
 *
 * Simple:
 *
 *    <sg:example>your code</sg:example>
 *
 * All options:
 *
 *    <sg:example codePreview="true" codeLanguage="html" customCode="{codeSnippet}" decodeEntities="true">your code</sg:example>
 *
 * @internal
 */
final class ExampleViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var bool
     */
    protected $escapeChildren = false;

    protected PageRenderer $pageRenderer;

    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->pageRenderer = $pageRenderer;
    }

    public function initializeArguments(): void
    {
        $this->registerArgument('codePreview', 'bool', 'if true, show the source code of the example', false, false);
        $this->registerArgument('codeLanguage', 'string', 'the code language identifier used for the code preview, e.g. html, php, etc.', false, false);
        $this->registerArgument('customCode', 'string', 'custom code displayed as code preview', false, false);
        $this->registerArgument('decodeEntities', 'bool', 'if true, entities like &lt; and &gt; are decoded', false, false);
        $this->registerArgument('rtlDirection', 'bool', 'if true direction is set to right-to-left', false, false);
    }

    public function render(): string
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/code-editor/element/code-mirror-element.js');
        // Compile and register code editor configuration
        GeneralUtility::makeInstance(CodeEditor::class)->registerConfiguration();

        $content = $this->renderChildren();

        if ($this->arguments['codePreview']) {
            if ($this->arguments['customCode']) {
                $code = $this->arguments['customCode'];
            } else {
                $code = $content;
            }

            $_lines = explode("\n", $code);
            $lines = [];
            foreach ($_lines as $line) {
                $line = preg_replace('/(\s)/', ' ', $line);
                if (trim($line) !== '') {
                    $lines[] = $line;
                }
            }
            $indentSize = strlen($lines[0]) - strlen(ltrim($lines[0]));
            $codeLines = [];
            foreach ($lines as $line) {
                $tmp = substr($line, $indentSize) ?: '';
                $spaces = strlen($tmp) - strlen(ltrim($tmp));
                $codeLines[] = str_repeat('  ', $spaces) . ltrim($line);
            }
            $code = implode(chr(10), $codeLines);

            $registry = GeneralUtility::makeInstance(ModeRegistry::class);
            if ($this->arguments['codeLanguage'] && ($registry->isRegistered($this->arguments['codeLanguage']))) {
                $mode = $registry->getByFormatCode($this->arguments['codeLanguage']);
            } else {
                $mode = $registry->getDefaultMode();
            }

            $codeMirrorConfig = [
                'mode' => GeneralUtility::jsonEncodeForHtmlAttribute($mode->getModule(), false),
                'readonly' => true,
            ];
            $attributes = [
                'wrap' => 'off',
                'rows' => count($lines),
            ];
        }

        $directionSetting = '';
        if ($this->arguments['rtlDirection']) {
            $directionSetting = 'dir="rtl"';
        }

        $uniqueId = uniqid('code');

        $markup = [];
        $markup[] = '<div class="styleguide-example">';
        $markup[] =     '<div class="styleguide-example-content" ' . $directionSetting . '>';
        $markup[] =         str_replace('<UNIQUEID>', $uniqueId, $content);
        $markup[] =     '</div>';
        if ($this->arguments['codePreview']) {
            $markup[] = '<div class="styleguide-example-code">';
            $markup[] =     '<div class="example example--code">';
            $markup[] =         '<typo3-t3editor-codemirror ' . GeneralUtility::implodeAttributes($codeMirrorConfig, true) . '>';
            $markup[] =             '<textarea ' . GeneralUtility::implodeAttributes($attributes, true) . '>';
            if ($this->arguments['decodeEntities']) {
                $markup[] =             htmlspecialchars_decode(str_replace('<UNIQUEID>', $uniqueId, $code));
            } else {
                $markup[] =             htmlspecialchars(str_replace('<UNIQUEID>', $uniqueId, $code));
            }
            $markup[] =             '</textarea>';
            $markup[] =         '</typo3-t3editor-codemirror>';
            $markup[] =     '</div>';
            $markup[] = '</div>';
        }
        $markup[] = '</div>';

        return implode('', $markup);
    }
}
