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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for container and single elements - their abstracts extend from here.
 */
abstract class AbstractNode implements NodeInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Instance of the node factory to create sub elements, container and single element expansions.
     *
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * Main data array to work on, given from parent to child elements
     *
     * @var array
     */
    protected array $data = [];

    /**
     * A list of default field information added to the element / container.
     *
     * @var array
     */
    protected $defaultFieldInformation = [];

    /**
     * A list of default field controls added to the element / container.
     * This property is often reset by single elements.
     *
     * @var array
     */
    protected $defaultFieldControl = [];

    /**
     * A list of default field wizards added to the element / container.
     * This property is often reset by single elements.
     *
     * @var array
     */
    protected $defaultFieldWizard = [];

    /**
     * Set data to data array and register node factory to render sub elements
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        $this->data = $data;
        $this->nodeFactory = $nodeFactory;
    }

    /**
     * Handler for single nodes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    abstract public function render();

    /**
     * Initialize the array that is returned to parent after calling. This structure
     * is identical for *all* nodes. Parent will merge the return of a child with its
     * own stuff and in itself return an array of the same structure.
     *
     * @return array
     */
    protected function initializeResultArray(): array
    {
        return [
            // @todo deprecate inline JavaScript in TYPO3 v12.0
            'additionalJavaScriptPost' => [],
            // @todo deprecate additionalHiddenFields in TYPO3 v12.0 - this return key is essentially
            //       useless. Elements can simply submit their hidden HTML fields in the html key.
            'additionalHiddenFields' => [],
            'additionalInlineLanguageLabelFiles' => [],
            'stylesheetFiles' => [],
            // can hold strings or arrays,
            // string = requireJS module,
            // array = requireJS module + callback e.g. array('TYPO3/Foo/Bar', 'function() {}')
            'requireJsModules' => [],
            'inlineData' => [],
            'html' => '',
        ];
    }

    /**
     * Merge existing data with a child return array.
     * The incoming $childReturn array should be initialized
     * using initializeResultArray() beforehand.
     *
     * @param array $existing Currently merged array
     * @param array $childReturn Array returned by child
     * @param bool $mergeHtml If false, the ['html'] section of $childReturn will NOT be added to $existing
     * @return array Result array
     */
    protected function mergeChildReturnIntoExistingResult(array $existing, array $childReturn, bool $mergeHtml = true): array
    {
        if ($mergeHtml && !empty($childReturn['html'])) {
            $existing['html'] .= LF . $childReturn['html'];
        }
        // @todo deprecate inline JavaScript in TYPO3 v12.0
        foreach ($childReturn['additionalJavaScriptPost'] ?? [] as $value) {
            $existing['additionalJavaScriptPost'][] = $value;
        }
        foreach ($childReturn['additionalHiddenFields'] ?? [] as $value) {
            $existing['additionalHiddenFields'][] = $value;
        }
        foreach ($childReturn['stylesheetFiles'] ?? [] as $value) {
            $existing['stylesheetFiles'][] = $value;
        }
        foreach ($childReturn['requireJsModules'] ?? [] as $module) {
            $existing['requireJsModules'][] = $module;
        }
        foreach ($childReturn['additionalInlineLanguageLabelFiles'] ?? [] as $inlineLanguageLabelFile) {
            $existing['additionalInlineLanguageLabelFiles'][] = $inlineLanguageLabelFile;
        }
        if (!empty($childReturn['inlineData'])) {
            $existingInlineData = $existing['inlineData'];
            $childInlineData = $childReturn['inlineData'];
            ArrayUtility::mergeRecursiveWithOverrule($existingInlineData, $childInlineData);
            $existing['inlineData'] = $existingInlineData;
        }
        return $existing;
    }

    /**
     * Build JSON string for validations rules.
     *
     * @param array $config
     * @return string
     */
    protected function getValidationDataAsJsonString(array $config): string
    {
        $validationRules = [];
        if (!empty($config['eval'])) {
            $evalList = GeneralUtility::trimExplode(',', $config['eval'] ?? '', true);
            foreach ($evalList as $evalType) {
                $validationRules[] = [
                    'type' => $evalType,
                ];
            }
        }
        if (!empty($config['range'])) {
            $newValidationRule = [
                'type' => 'range',
            ];
            if (!empty($config['range']['lower'])) {
                $newValidationRule['lower'] = $config['range']['lower'];
            }
            if (!empty($config['range']['upper'])) {
                $newValidationRule['upper'] = $config['range']['upper'];
            }
            $validationRules[] = $newValidationRule;
        }
        if (!empty($config['maxitems']) || !empty($config['minitems'])) {
            $minItems = isset($config['minitems']) ? (int)$config['minitems'] : 0;
            $maxItems = isset($config['maxitems']) ? (int)$config['maxitems'] : 99999;
            $type = $config['type'] ?: 'range';
            $validationRules[] = [
                'type' => $type,
                'minItems' => $minItems,
                'maxItems' => $maxItems,
            ];
        }
        if (!empty($config['required'])) {
            $validationRules[] = ['type' => 'required'];
        }
        return json_encode($validationRules);
    }
}
