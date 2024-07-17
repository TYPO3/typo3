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
 * Render code snippets in a usable way
 *
 * @internal
 */
final class CodeViewHelper extends AbstractViewHelper
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
        $this->registerArgument('language', 'string', 'the language identifier, e.g. html, php, etc.', true);
        $this->registerArgument('codeonly', 'bool', 'if true show only the code but not the example', false, false);
        $this->registerArgument('exampleonly', 'bool', 'if true show only the example but not the code', false, false);
        $this->registerArgument('colorschemes', 'bool', 'if true show example variants forced in auto, light and dark', false, false);
        $this->registerArgument('decodeEntities', 'bool', 'if true entities like &lt; and &gt; are decoded', false, false);
    }

    public function render(): string
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/code-editor/element/code-mirror-element.js');
        // Compile and register code editor configuration
        GeneralUtility::makeInstance(CodeEditor::class)->registerConfiguration();

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
            $schemes = ['default'];
            if ($this->arguments['colorschemes']) {
                $schemes = ['light', 'dark'];
            }
            foreach ($schemes as $scheme) {
                $markup[] = '<div class="example" data-color-scheme="' . $scheme . '">';
                $markup[] = str_replace('<UNIQUEID>', uniqid($scheme), $content);
                $markup[] = '</div>';
            }
        }
        if (!$this->arguments['exampleonly']) {
            $registry = GeneralUtility::makeInstance(ModeRegistry::class);
            if ($registry->isRegistered($this->arguments['language'])) {
                $mode = $registry->getByFormatCode($this->arguments['language']);
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

            $markup[] = '<div class="example example--code">';
            $markup[] = '<typo3-t3editor-codemirror ' . GeneralUtility::implodeAttributes($codeMirrorConfig, true) . '>';
            $markup[] = '<textarea ' . GeneralUtility::implodeAttributes($attributes, true) . '>';
            if ($this->arguments['decodeEntities']) {
                $markup[] = htmlspecialchars_decode(str_replace('<UNIQUEID>', uniqid('code'), $content));
            } else {
                $markup[] = htmlspecialchars(str_replace('<UNIQUEID>', uniqid('code'), $content));
            }
            $markup[] = '</textarea>';
            $markup[] = '</typo3-t3editor-codemirror>';
            $markup[] = '</div>';
        }
        return implode('', $markup);
    }
}
