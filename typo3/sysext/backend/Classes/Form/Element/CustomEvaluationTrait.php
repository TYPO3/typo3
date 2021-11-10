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

namespace TYPO3\CMS\Backend\Form\Element;

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait handling custom `eval` implementations.
 */
trait CustomEvaluationTrait
{
    protected function resolveJavaScriptEvaluation(array $resultArray, string $name, ?object $evalObject): array
    {
        if (!is_object($evalObject) || !method_exists($evalObject, 'returnFieldJS')) {
            return $resultArray;
        }

        $javaScriptEvaluation = $evalObject->returnFieldJS();
        if ($javaScriptEvaluation instanceof JavaScriptModuleInstruction) {
            // just use the module name and export-name
            $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
                $javaScriptEvaluation->getName(),
                $javaScriptEvaluation->getExportName()
            )->invoke('registerCustomEvaluation', $name);
        } else {
            // @todo deprecate inline JavaScript in TYPO3 v12.0
            $resultArray['additionalJavaScriptPost'][] = sprintf(
                'TBE_EDITOR.customEvalFunctions[%s] = function(value) { %s };',
                GeneralUtility::quoteJSvalue($name),
                $javaScriptEvaluation
            );
        }

        return $resultArray;
    }
}
