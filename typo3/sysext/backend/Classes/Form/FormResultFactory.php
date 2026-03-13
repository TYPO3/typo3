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

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

/**
 * Transforms the raw array returned by FormEngine node rendering
 * into a typed FormResult value object.
 *
 * @internal This class and its exposed method and method signatures will change
 */
class FormResultFactory
{
    public function create(array $resultArray): FormResult
    {
        $javaScriptModules = [];
        foreach ($resultArray['javaScriptModules'] ?? [] as $module) {
            if (!$module instanceof JavaScriptModuleInstruction) {
                throw new \LogicException(
                    sprintf(
                        'Module must be a %s, type "%s" given',
                        JavaScriptModuleInstruction::class,
                        gettype($module)
                    ),
                    1663860284
                );
            }
            $javaScriptModules[] = $module;
        }

        return new FormResult(
            html: $resultArray['html'],
            javaScriptModules: $javaScriptModules,
            stylesheetFiles: array_unique(array_values($resultArray['stylesheetFiles'] ?? [])),
            inlineData: $resultArray['inlineData'],
            additionalInlineLanguageLabelFiles: $resultArray['additionalInlineLanguageLabelFiles'],
            hiddenFieldsHtml: $resultArray['additionalHiddenFields'],
        );
    }
}
