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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This class and its exposed method and method signatures will change
 */
trait FormResultTrait
{
    /**
     * @todo This is a temporary "solution" and shall be handled in JavaScript directly
     */
    protected function serializeJavaScriptModuleInstructionItems(JavaScriptModuleInstruction $instruction): array
    {
        $inlineCode = [];
        $subjectRef = $instruction->getExportName() ? '__esModule.' . $instruction->getExportName() : 'subjectRef';
        foreach ($instruction->getItems() as $item) {
            // only `invoke` & `instance` are supported, `assign` is missing, on purpose!
            if ($item['type'] === JavaScriptModuleInstruction::ITEM_INVOKE) {
                $inlineCode[] = sprintf(
                    // `__esModule.FormThingy.apply(__esModule.FormThingy, JSON.parse('[1,2]'))`
                    '%s.%s.apply(%s, JSON.parse(\'%s\'));',
                    $subjectRef,
                    $item['method'],
                    $subjectRef,
                    GeneralUtility::jsonEncodeForJavaScript($item['args'] ?? [])
                );
            } elseif ($item['type'] === JavaScriptModuleInstruction::ITEM_INSTANCE) {
                $args = $item['args'] ?? [];
                // this `null` is `thisArg` scope of `Function.bind`,
                // which will be reset when invoking `new`
                array_unshift($args, null);
                $inlineCode[] = sprintf(
                    // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Function/bind
                    // `new (__esModule.FormThingy.bind.apply(__esModule.FormThingy, JSON.parse('[null,1,2]')))`
                    'new (%s.bind.apply(%s, JSON.parse(\'%s\')));',
                    $subjectRef,
                    $subjectRef,
                    GeneralUtility::jsonEncodeForJavaScript($args)
                );
            }
        }
        return $inlineCode;
    }
}
