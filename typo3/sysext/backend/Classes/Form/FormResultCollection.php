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
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Aggregates rendering results from multiple FormEngine elements and provides
 * a deduplicated, merged view of all required page assets so they can be
 * registered with the PageRenderer in a single pass.
 *
 * This is typically in use when a controller allows to edit multiple
 * forms in one request.
 *
 * @internal This class may change any time or vanish altogether
 */
class FormResultCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var FormResult[]
     */
    private array $results = [];

    public function add(FormResult $result): void
    {
        $this->results[] = $result;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->results);
    }

    public function count(): int
    {
        return count($this->results);
    }

    /**
     * @return JavaScriptModuleInstruction[]
     */
    public function getJavaScriptModules(): array
    {
        $modules = [];
        foreach ($this->results as $result) {
            foreach ($result->javaScriptModules as $module) {
                if (!in_array($module, $modules, true)) {
                    $modules[] = $module;
                }
            }
        }
        return $modules;
    }

    /**
     * @return string[]
     */
    public function getStylesheetFiles(): array
    {
        $files = [];
        foreach ($this->results as $result) {
            foreach ($result->stylesheetFiles as $file) {
                $files[] = $file;
            }
        }
        return array_unique($files);
    }

    /**
     * @return string[]
     */
    public function getInlineData(): array
    {
        $inlineData = [];
        foreach ($this->results as $result) {
            ArrayUtility::mergeRecursiveWithOverrule($inlineData, $result->inlineData);
        }
        return $inlineData;
    }

    /**
     * @return string[]
     */
    public function getAdditionalInlineLanguageLabelFiles(): array
    {
        $files = [];
        foreach ($this->results as $result) {
            foreach ($result->additionalInlineLanguageLabelFiles as $file) {
                $files[] = $file;
            }
        }
        return array_unique($files);
    }

    /**
     * @return string[]
     * @deprecated since v14.2, will be removed in v15. Add hidden fields to the 'html' key directly.
     */
    public function getHiddenFieldsHtml(): array
    {
        $fields = [];
        foreach ($this->results as $result) {
            foreach ($result->hiddenFieldsHtml as $field) {
                $fields[] = $field;
            }
        }
        return array_unique($fields);
    }

    public function getHtml(): string
    {
        $html = '';
        foreach ($this->results as $result) {
            $html .= $result->html;
        }
        return $html;
    }
}
