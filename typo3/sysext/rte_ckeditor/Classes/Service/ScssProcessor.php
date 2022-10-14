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

namespace TYPO3\CMS\RteCKEditor\Service;

use ScssPhp\ScssPhp\Compiler;

/**
 * Used only for RTE CKEditor to prepare SCSS for parsing.
 * Never use this class in your own code.
 *
 * @internal This API is used internally only.
 */
class ScssProcessor
{
    public function __construct(
        protected readonly Compiler $scssCompiler
    ) {
    }

    /**
     * Compiles SCSS to CSS.
     */
    public function compileToCss(string $scssSource): string
    {
        return $this->scssCompiler->compileString($scssSource)->getCss();
    }

    /**
     * Prefixes CSS source content for later SCSS processing.
     */
    public function prefixCssForScss(string $cssPrefix, string $cssSource): string
    {
        // replace body and html with the prefix
        foreach (['html', 'body'] as $cssSelector) {
            $cssSource = preg_replace_callback(
                '/(?:^|[\s\/},])(?<![#.])(' . $cssSelector . ')\s*[{,][^}]*(?=})/mi',
                static function ($matches) {
                    return str_replace($matches[1], '&', $matches[0]);
                },
                $cssSource
            );
        }

        // Some CSS minifier remove the semicolon before the curly brace
        // While this is valid CSS, the ScssPHP Parser is unable to identify
        // the end of a declaration block. We are adding them, to avoid
        // parsing errors. Superfluous semicolons will be dropped by the parser.
        $cssSource = str_replace('}', ';}', $cssSource);

        // add prefix to all CSS definitions by wrapping it in SCSS group
        $cssSource = sprintf("%s {\n%s\n}", $cssPrefix, $cssSource);

        // Moving CSS variables assigned to :root to the new parent.
        $cssSource = str_replace(':root', '&', $cssSource);

        return $cssSource;
    }
}
