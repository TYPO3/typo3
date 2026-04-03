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

namespace TYPO3\CMS\Fluid\ViewHelpers\Asset;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\DirectiveHashCollection;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for inline style attributes that need CSP hash coverage via `style-src-attr`.
 *
 * When `csp` is true (default), the style value is hashed and registered with the
 * HashCollection so that the CSP header includes the corresponding `sha256-...` hash.
 *
 * Usage:
 * ```
 *    <div style="{f:asset.styleAttr(value: 'color: green; text-decoration: underline;', csp: true)}">...</div>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-asset-styleattr
 */
final class StyleAttrViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly DirectiveHashCollection $directiveHashCollection) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'The inline style value (e.g. "color: green; text-decoration: underline;")', true);
        $this->registerArgument('csp', 'bool', 'Whether to collect a CSP hash for this style value', false, true);
    }

    public function render(): string
    {
        $value = trim($this->arguments['value'] ?? $this->renderChildren());
        if ($this->arguments['csp'] ?? true) {
            $this->directiveHashCollection->addInlineHash(Directive::StyleSrcAttr, $value);
        }
        return $value;
    }
}
