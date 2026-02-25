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

namespace TYPO3\CMS\Backend\Form;

/**
 * Immutable value object carrying the rendering output of a single FormEngine
 * element — its HTML fragment together with all page-level assets (JavaScript
 * modules, stylesheets, language labels) the element needs to function.
 */
final readonly class FormResult
{
    public function __construct(
        public string $html,
        public array $javaScriptModules = [],
        public array $stylesheetFiles = [],
        public array $inlineData = [],
        public array $additionalInlineLanguageLabelFiles = [],
        /** @deprecated since v14.2, will be removed in v15. Add hidden fields to the 'html' key directly. */
        public array $hiddenFieldsHtml = [],
    ) {}
}
