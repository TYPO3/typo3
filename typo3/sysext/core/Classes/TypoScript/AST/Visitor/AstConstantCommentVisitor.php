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

namespace TYPO3\CMS\Core\TypoScript\AST\Visitor;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath\CurrentObjectPath;
use TYPO3\CMS\Core\TypoScript\AST\Node\NodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStreamInterface;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Gather comments suitable for constant editor.
 *
 * @internal This is a specific Backend implementation and is not considered part of the Public TYPO3 API.
 */
final class AstConstantCommentVisitor implements AstVisitorInterface
{
    private array $categories = [
        'basic' => [
            'label' => 'Basic',
            'usageCount' => 0,
        ],
        'menu' => [
            'label' => 'Menu',
            'usageCount' => 0,
        ],
        'content' => [
            'label' => 'Content',
            'usageCount' => 0,
        ],
        'page' => [
            'label' => 'Page',
            'usageCount' => 0,
        ],
        'advanced' => [
            'label' => 'Advanced',
            'usageCount' => 0,
        ],
        'all' => [
            'label' => 'All',
            'usageCount' => 0,
        ],
    ];

    private array $subCategories = [
        'enable' => [
            'label' => 'Enable features',
            'sorting' => 'a',
        ],
        'dims' => [
            'label' => 'Dimensions, widths, heights, pixels',
            'sorting' => 'b',
        ],
        'file' => [
            'label' => 'Files',
            'sorting' => 'c',
        ],
        'typo' => [
            'label' => 'Typography',
            'sorting' => 'd',
        ],
        'color' => [
            'label' => 'Colors',
            'sorting' => 'e',
        ],
        'links' => [
            'label' => 'Links and targets',
            'sorting' => 'f',
        ],
        'language' => [
            'label' => 'Language specific constants',
            'sorting' => 'g',
        ],
        'cheader' => [
            'label' => 'Content: \'Header\'',
            'sorting' => 'ma',
        ],
        'cheader_g' => [
            'label' => 'Content: \'Header\', Graphical',
            'sorting' => 'ma',
        ],
        'ctext' => [
            'label' => 'Content: \'Text\'',
            'sorting' => 'mb',
        ],
        'cimage' => [
            'label' => 'Content: \'Image\'',
            'sorting' => 'md',
        ],
        'ctextmedia' => [
            'label' => 'Content: \'Textmedia\'',
            'sorting' => 'ml',
        ],
        'cbullets' => [
            'label' => 'Content: \'Bullet list\'',
            'sorting' => 'me',
        ],
        'ctable' => [
            'label' => 'Content: \'Table\'',
            'sorting' => 'mf',
        ],
        'cuploads' => [
            'label' => 'Content: \'Filelinks\'',
            'sorting' => 'mg',
        ],
        'cmultimedia' => [
            'label' => 'Content: \'Multimedia\'',
            'sorting' => 'mh',
        ],
        'cmedia' => [
            'label' => 'Content: \'Media\'',
            'sorting' => 'mr',
        ],
        'cmailform' => [
            'label' => 'Content: \'Form\'',
            'sorting' => 'mi',
        ],
        'csearch' => [
            'label' => 'Content: \'Search\'',
            'sorting' => 'mj',
        ],
        'clogin' => [
            'label' => 'Content: \'Login\'',
            'sorting' => 'mk',
        ],
        'cmenu' => [
            'label' => 'Content: \'Menu/Sitemap\'',
            'sorting' => 'mm',
        ],
        'cshortcut' => [
            'label' => 'Content: \'Insert records\'',
            'sorting' => 'mn',
        ],
        'clist' => [
            'label' => 'Content: \'List of records\'',
            'sorting' => 'mo',
        ],
        'chtml' => [
            'label' => 'Content: \'HTML\'',
            'sorting' => 'mq',
        ],
    ];

    /**
     * Helper hack variable to have a unique sub category order if no sub category is given.
     */
    private int $subCategoryCounter = 0;

    private array $currentTemplateFlatConstants = [];

    private array $constants = [];

    public function visitBeforeChildren(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
        if ($node instanceof RootNode) {
            $rootNodeComments = $rootNode->getComments();
            foreach ($rootNodeComments as $comment) {
                $this->subCategoryCounter++;
                // Additional custom categories are attached as comments to root node
                $this->parseCustomCategoryAndSubCategories($comment);
            }
        } else {
            $nodeComments = $node->getComments();
            foreach ($nodeComments as $comment) {
                $this->subCategoryCounter++;
                $parsedCommentArray = $this->parseNodeComment($comment, $node->getName(), $node->getValue());
                if (empty($parsedCommentArray)) {
                    continue;
                }
                $currentDottedPath = $currentObjectPath->getPathAsString();
                if (array_key_exists($currentDottedPath, $this->constants)) {
                    // A constant definition can be defined only once. Stop when trying to override.
                    continue;
                }
                $parsedCommentArray['name'] = $currentDottedPath;
                $parsedCommentArray['idName'] = str_replace('.', '-', $currentDottedPath);
                $parsedCommentArray['value'] = $node->getValue();
                $parsedCommentArray['default_value'] = $node->getPreviousValue() ?? $node->getValue() ?? '[Empty]';
                $parsedCommentArray['isInCurrentTemplate'] = false;
                if (array_key_exists($currentDottedPath, $this->currentTemplateFlatConstants)) {
                    $parsedCommentArray['isInCurrentTemplate'] = true;
                }
                $this->constants[$currentDottedPath] = $parsedCommentArray;
            }
        }
    }

    public function setCurrentTemplateFlatConstants(array $currentTemplateFlatConstants)
    {
        $this->currentTemplateFlatConstants = $currentTemplateFlatConstants;
    }

    public function getConstants(): array
    {
        return $this->constants;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    private function parseNodeComment(TokenStreamInterface $commentTokenStream, string $nodeName, ?string $currentValue = null): array
    {
        $languageService = $this->getLanguageService();
        $parsedCommentArray = [];
        $commentTokenStream->reset();
        $trimmedTokenStream = new TokenStream();
        while ($token = $commentTokenStream->getNext()) {
            if ($token->getType() !== TokenType::T_BLANK) {
                $trimmedTokenStream->append($token);
            }
        }
        $firstTokenType = $trimmedTokenStream->peekNext()->getType();
        if ($firstTokenType !== TokenType::T_COMMENT_ONELINE_HASH && $firstTokenType !== TokenType::T_COMMENT_ONELINE_DOUBLESLASH) {
            // Ignore multiline comments, only '#' and '//' allowed here
            return $parsedCommentArray;
        }
        $commentString = trim((string)$trimmedTokenStream);
        // Get rid of '#' and '//'
        $commentString = trim(preg_replace('/^[#\\/]*/', '', $commentString));
        if (empty($commentString)) {
            return $parsedCommentArray;
        }
        // "# cat=my custom: custom1/customsub1; type=string; label=custom1 customsub1 test1"
        $commentParts = explode(';', $commentString);
        foreach ($commentParts as $commentPart) {
            if (!str_contains($commentPart, '=')) {
                // Whatever it is, we ignore it.
                continue;
            }
            $partArray = explode('=', $commentPart, 2);
            $partKey = strtolower(trim($partArray[0] ?? ''));
            $partValue = trim($partArray[1] ?? '');
            if (empty($partKey) || empty($partValue)) {
                continue;
            }
            if ($partKey === 'type') {
                if (str_starts_with($partValue, 'int+')) {
                    $parsedCommentArray['type'] = 'int+';
                    $parsedCommentArray['typeIntPlusMin'] = 0;
                    preg_match('/int\+\[(.*)\]/is', $partValue, $typeMatches);
                    if (!empty($typeMatches[1]) && str_contains($typeMatches[1], '-')) {
                        $intPlusExplodedRange = GeneralUtility::intExplode('-', $typeMatches[1]);
                        $parsedCommentArray['typeIntPlusMin'] = $intPlusExplodedRange[0];
                        $parsedCommentArray['typeHint'] = 'Greater than ' . $intPlusExplodedRange[0];
                        if ($intPlusExplodedRange[1] > 0) {
                            $parsedCommentArray['typeIntPlusMax'] = $intPlusExplodedRange[1];
                            $parsedCommentArray['typeHint'] = 'Range ' . $intPlusExplodedRange[0] . ' - ' . $intPlusExplodedRange[1];
                        }
                    }
                } elseif (str_starts_with($partValue, 'int')) {
                    preg_match('/int\[(.*)\]/is', $partValue, $typeMatches);
                    $parsedCommentArray['type'] = 'int';
                    if (!empty($typeMatches[1]) && str_contains($typeMatches[1], '-')) {
                        $rangeArray = mb_str_split($typeMatches[1]);
                        $negativeStart = false;
                        $negativeStop = false;
                        $gotSeparatorDash = false;
                        $start = null;
                        $stop = null;
                        foreach ($rangeArray as $index => $char) {
                            if ($index === 0 && $char === '-') {
                                $negativeStart = true;
                            } elseif ($char === '-' && !$gotSeparatorDash) {
                                $gotSeparatorDash = true;
                            } elseif (!$gotSeparatorDash) {
                                $start .= $char;
                            } elseif ($stop === '' && $char === '-') {
                                $negativeStop = true;
                            } else {
                                $stop .= $char;
                            }
                        }
                        if ($start !== null) {
                            if ($negativeStart) {
                                $start = (int)$start * -1;
                            }
                            $parsedCommentArray['typeIntMin'] = (string)$start;
                            $parsedCommentArray['typeHint'] = 'Greater than ' . $start;
                        }
                        if ($stop !== null) {
                            if ($negativeStop) {
                                $stop = (int)$stop * -1;
                            }
                            $parsedCommentArray['typeIntMax'] = (string)$stop;
                            $parsedCommentArray['typeHint'] = 'Range ' . $start . ' - ' . $stop;
                        }
                    }
                } elseif ($partValue === 'wrap') {
                    $parsedCommentArray['type'] = 'wrap';
                    $splitValue = explode('|', $currentValue ?? '');
                    $parsedCommentArray['wrapStart'] = $splitValue[0] ?? '';
                    $parsedCommentArray['wrapEnd'] = $splitValue[1] ?? '';
                } elseif (str_starts_with($partValue, 'offset')) {
                    $parsedCommentArray['type'] = 'offset';
                    preg_match('/offset\[(.*)\]/is', $partValue, $typeMatches);
                    $labelArray = explode(',', $typeMatches[1] ?? '');
                    $valueArray = explode(',', $currentValue ?? '');
                    $parsedCommentArray['labelValueArray'] = [
                        [
                            'label' => (!empty($labelArray[0])) ? $labelArray[0] : 'x',
                            'value' => (!empty($valueArray[0])) ? $valueArray[0] : '',
                        ],
                        [
                            'label' => $labelArray[1] ?? 'y',
                            'value' => $valueArray[1] ?? '',
                        ],
                    ];
                    for ($i = 2; $i <= 5; $i++) {
                        if (!($labelArray[$i] ?? false)) {
                            break;
                        }
                        $parsedCommentArray['labelValueArray'][] = [
                            'label' => $labelArray[$i],
                            'value' => $valueArray[$i] ?? '',
                        ];
                    }
                } elseif (str_starts_with($partValue, 'options')) {
                    preg_match('/options\\s*\[(.*)\]/is', $partValue, $typeMatches);
                    if (!empty($typeMatches[1] ?? '')) {
                        $parsedCommentArray['type'] = 'options';
                        $labelValueStringArray = GeneralUtility::trimExplode(',', $typeMatches[1], true);
                        foreach ($labelValueStringArray as $labelValueString) {
                            $labelValueArray = explode('=', $labelValueString, 2);
                            $label = $labelValueArray[0];
                            $value = $labelValueArray[1] ?? $labelValueArray[0];
                            $selected = false;
                            if ($value === $currentValue) {
                                $selected = true;
                            }
                            $parsedCommentArray['labelValueArray'][] = [
                                'label' => $languageService->sL($label),
                                'value' => $value,
                                'selected' => $selected,
                            ];
                        }
                    }
                } elseif (str_starts_with($partValue, 'boolean')) {
                    $parsedCommentArray['type'] = 'boolean';
                    preg_match('/boolean\\s*\[(.*)\]/is', $partValue, $typeMatches);
                    $parsedCommentArray['trueValue'] = '1';
                    if (!empty($typeMatches[1] ?? '')) {
                        $parsedCommentArray['trueValue'] = $typeMatches[1];
                    }
                } elseif (str_starts_with($partValue, 'user')) {
                    preg_match('/user\\s*\[(.*)\]/is', $partValue, $typeMatches);
                    if (!empty($typeMatches[1] ?? '')) {
                        $parsedCommentArray['type'] = 'user';
                        $userFunction = $typeMatches[1];
                        $userFunctionParams = [
                            'fieldName' => $nodeName,
                            'fieldValue' => $currentValue,
                        ];
                        $parsedCommentArray['html'] = (string)GeneralUtility::callUserFunction($userFunction, $userFunctionParams);
                    }
                } elseif ($partValue === 'comment') {
                    $parsedCommentArray['type'] = 'comment';
                } elseif ($partValue === 'color') {
                    $parsedCommentArray['type'] = 'color';
                } else {
                    $parsedCommentArray['type'] = 'string';
                }
            } elseif ($partKey === 'cat') {
                $categorySplitArray = explode('/', strtolower($partValue));
                $mainCategory = strtolower(trim($categorySplitArray[0] ?? ''));
                if (empty($mainCategory)) {
                    return [];
                }
                if (isset($this->categories[$mainCategory])) {
                    $this->categories[$mainCategory]['usageCount']++;
                } else {
                    $this->categories[$mainCategory] = [
                        'usageCount' => 1,
                        'label' => $mainCategory,
                    ];
                }
                $parsedCommentArray['cat'] = $mainCategory;
                $subCategory = trim($categorySplitArray[1] ?? '');
                $subCategoryOrder = trim($categorySplitArray[2] ?? '');
                if ($subCategory && array_key_exists($subCategory, $this->subCategories)) {
                    $parsedCommentArray['subcat_name'] = $subCategory;
                    $parsedCommentArray['subcat_label'] = $languageService->sL($this->subCategories[$subCategory]['label']);
                    $sortIdentifier = empty($subCategoryOrder) ? $this->subCategoryCounter : $subCategoryOrder;
                    $parsedCommentArray['subcat_sorting_first'] = $this->subCategories[$subCategory]['sorting'];
                    $parsedCommentArray['subcat_sorting_second'] = $sortIdentifier . 'z';
                } elseif ($subCategoryOrder) {
                    $parsedCommentArray['subcat_name'] = 'other';
                    $parsedCommentArray['subcat_label'] = 'Other';
                    $parsedCommentArray['subcat_sorting_first'] = 'o';
                    $parsedCommentArray['subcat_sorting_second'] = $subCategoryOrder . 'z';
                } else {
                    $parsedCommentArray['subcat_name'] = 'other';
                    $parsedCommentArray['subcat_label'] = 'Other';
                    $parsedCommentArray['subcat_sorting_first'] = 'o';
                    $parsedCommentArray['subcat_sorting_second'] = $this->subCategoryCounter . 'z';
                }
            } elseif ($partKey === 'label') {
                $fullLabel = $languageService->sL($partValue);
                $splitLabelArray = explode(':', $fullLabel, 2);
                $parsedCommentArray['label'] = $splitLabelArray[0] ?? '';
                $parsedCommentArray['description'] = $splitLabelArray[1] ?? '';
            }
        }
        if (!array_key_exists('cat', $parsedCommentArray)) {
            // At least 'category' must be there, everything else is optional.
            return [];
        }
        $parsedCommentArray['type'] ??= 'string';
        return $parsedCommentArray;
    }

    /**
     * Parse RootNode comments for additional custom categories and sub categories
     * and register them in $this properties.
     */
    private function parseCustomCategoryAndSubCategories(TokenStreamInterface $commentTokenStream): void
    {
        $languageService = $this->getLanguageService();
        $firstTokenType = $commentTokenStream->peekNext()->getType();
        if ($firstTokenType !== TokenType::T_COMMENT_ONELINE_HASH && $firstTokenType !== TokenType::T_COMMENT_ONELINE_DOUBLESLASH) {
            // Ignore multiline comments, only '#' and '//' allowed here
            return;
        }
        $commentString = trim((string)$commentTokenStream);
        // Get rid of '#' and '//'
        $commentString = trim(preg_replace('/^[#\\/]*/', '', $commentString));
        if (empty($commentString)) {
            return;
        }
        // "# customcategory=myCustomCategoryKey=My custom category label"
        if (str_contains($commentString, '=') && str_starts_with(strtolower($commentString), 'customcategory')) {
            $customCategoryArray = explode('=', $commentString, 3);
            if (strtolower(trim($customCategoryArray[0])) !== 'customcategory'
                || empty(trim($customCategoryArray[1]))
                || empty(trim($customCategoryArray[2]))
            ) {
                return;
            }
            $categoryKey = $customCategoryArray[1];
            $categoryLabel = $customCategoryArray[2];
            if (!isset($this->categories[$categoryKey])) {
                $this->categories[$categoryKey] = [
                    'usageCount' => 0,
                    'label' => $languageService->sL($categoryLabel),
                ];
            }
            return;
        }
        // "customsubcategory=120=My custom sub category label"
        if (str_contains($commentString, '=') && str_starts_with(strtolower($commentString), 'customsubcategory')) {
            $customSubCategoryArray = explode('=', $commentString, 3);
            if (strtolower(trim($customSubCategoryArray[0])) !== 'customsubcategory'
                || empty(trim($customSubCategoryArray[1]))
                || empty(trim($customSubCategoryArray[2]))
            ) {
                return;
            }
            $subCategoryKey = $customSubCategoryArray[1];
            $subCategoryLabel = $customSubCategoryArray[2];
            if (!isset($this->subCategories[$subCategoryKey])) {
                $this->subCategories[$subCategoryKey] = [
                    'label' => $languageService->sL($subCategoryLabel),
                    'sorting' => $this->subCategoryCounter,
                ];
            }
        }
    }

    public function visit(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
        // Implement interface
    }

    public function visitAfterChildren(RootNode $rootNode, NodeInterface $node, CurrentObjectPath $currentObjectPath, int $currentDepth): void
    {
        // Implement interface
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
