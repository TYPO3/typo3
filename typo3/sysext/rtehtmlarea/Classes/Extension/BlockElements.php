<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * BlockElements extension for htmlArea RTE
 */
class BlockElements extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'BlockElements';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'formatblock, indent, outdent, blockquote, insertparagraphbefore, insertparagraphafter, left, center, right, justifyfull, orderedlist, unorderedlist, line';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'formatblock' => 'FormatBlock',
        'indent' => 'Indent',
        'outdent' => 'Outdent',
        'blockquote' => 'Blockquote',
        'insertparagraphbefore' => 'InsertParagraphBefore',
        'insertparagraphafter' => 'InsertParagraphAfter',
        'left' => 'JustifyLeft',
        'center' => 'JustifyCenter',
        'right' => 'JustifyRight',
        'justifyfull' => 'JustifyFull',
        'orderedlist' => 'InsertOrderedList',
        'unorderedlist' => 'InsertUnorderedList',
        'line' => 'InsertHorizontalRule'
    ];

    /**
     * List of default block elements
     *
     * @var array
     */
    protected $defaultBlockElements = [
        'none' => 'No block',
        'p' => 'Paragraph',
        'h1' => 'Heading 1',
        'h2' => 'Heading 2',
        'h3' => 'Heading 3',
        'h4' => 'Heading 4',
        'h5' => 'Heading 5',
        'h6' => 'Heading 6',
        'pre' => 'Preformatted',
        'address' => 'Address',
        'article' => 'Article',
        'aside' => 'Aside',
        'blockquote' => 'Long quotation',
        'div' => 'Container',
        'footer' => 'Footer',
        'header' => 'Header',
        'nav' => 'Navigation',
        'section' => 'Section'
    ];

    /**
     * Default order of block elements
     *
     * @var string
     */
    protected $defaultBlockElementsOrder = 'none, p, h1, h2, h3, h4, h5, h6, pre, address, article, aside, blockquote, div, footer, header, nav, section';

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins, in this case, JS configuration of block elements
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        if (in_array('formatblock', $this->toolbar)) {
            if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.']['formatblock.'])) {
                $jsArray[] = 'RTEarea[editornumber].buttons.formatblock = new Object();';
            }
            // Default block elements
            $hideItems = [];
            $addItems = [];
            $restrictTo = ['*'];
            $blockElementsOrder = $this->defaultBlockElementsOrder;
            $prefixLabelWithTag = false;
            $postfixLabelWithTag = false;
            // Processing PageTSConfig
            if (is_array($this->configuration['thisConfig']['buttons.']) && is_array($this->configuration['thisConfig']['buttons.']['formatblock.'])) {
                // Removing elements
                if ($this->configuration['thisConfig']['buttons.']['formatblock.']['removeItems']) {
                    $hideItems = GeneralUtility::trimExplode(',', $this->cleanList(GeneralUtility::strtolower($this->configuration['thisConfig']['buttons.']['formatblock.']['removeItems'])), true);
                }
                // Adding elements
                if ($this->configuration['thisConfig']['buttons.']['formatblock.']['addItems']) {
                    $addItems = GeneralUtility::trimExplode(',', $this->cleanList(GeneralUtility::strtolower($this->configuration['thisConfig']['buttons.']['formatblock.']['addItems'])), true);
                }
                // Restriction clause
                if ($this->configuration['thisConfig']['buttons.']['formatblock.']['restrictToItems']) {
                    $restrictTo = GeneralUtility::trimExplode(',', $this->cleanList('none,' . GeneralUtility::strtolower($this->configuration['thisConfig']['buttons.']['formatblock.']['restrictToItems'])), true);
                }
                // Elements order
                if ($this->configuration['thisConfig']['buttons.']['formatblock.']['orderItems']) {
                    $blockElementsOrder = 'none,' . GeneralUtility::strtolower($this->configuration['thisConfig']['buttons.']['formatblock.']['orderItems']);
                }
                $prefixLabelWithTag = $this->configuration['thisConfig']['buttons.']['formatblock.']['prefixLabelWithTag'] ? true : $prefixLabelWithTag;
                $postfixLabelWithTag = $this->configuration['thisConfig']['buttons.']['formatblock.']['postfixLabelWithTag'] ? true : $postfixLabelWithTag;
            }
            // Adding custom items
            $blockElementsOrder = array_merge(GeneralUtility::trimExplode(',', $this->cleanList($blockElementsOrder), true), $addItems);
            // Add div element if indent is configured in the toolbar
            if (in_array('indent', $this->toolbar) || in_array('outdent', $this->toolbar)) {
                $blockElementsOrder = array_merge($blockElementsOrder, ['div']);
            }
            // Add blockquote element if blockquote is configured in the toolbar
            if (in_array('blockquote', $this->toolbar)) {
                $blockElementsOrder = array_merge($blockElementsOrder, ['blockquote']);
            }
            // Remove items
            $blockElementsOrder = array_diff($blockElementsOrder, $hideItems);
            // Applying User TSConfig restriction
            if (!in_array('*', $restrictTo)) {
                $blockElementsOrder = array_intersect($blockElementsOrder, $restrictTo);
            }
            // Localizing the options
            $blockElementsOptions = [];
            $labels = [];
            if (is_array($this->configuration['thisConfig']['buttons.']) && is_array($this->configuration['thisConfig']['buttons.']['formatblock.']) && is_array($this->configuration['thisConfig']['buttons.']['formatblock.']['items.'])) {
                $labels = $this->configuration['thisConfig']['buttons.']['formatblock.']['items.'];
            }
            foreach ($blockElementsOrder as $item) {
                $blockElementsOptions[$item] = $this->getLanguageService()->sL(
                    'LLL:EXT:rtehtmlarea/Resources/Private/Language/Plugins/BlockElements/locallang.xlf:' . $this->defaultBlockElements[$item]
                );
                // Getting custom labels
                if (is_array($labels[$item . '.']) && $labels[$item . '.']['label']) {
                    $blockElementsOptions[$item] = $this->getPageConfigLabel($labels[$item . '.']['label']);
                }
                $blockElementsOptions[$item] = ($prefixLabelWithTag && $item != 'none' ? $item . ' - ' : '') . $blockElementsOptions[$item] . ($postfixLabelWithTag && $item != 'none' ? ' - ' . $item : '');
            }
            $first = array_shift($blockElementsOptions);
            // Sorting the options
            if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.']['formatblock.']) || !$this->configuration['thisConfig']['buttons.']['formatblock.']['orderItems']) {
                asort($blockElementsOptions);
            }
            // Generating the javascript options
            $JSBlockElements = [];
            $JSBlockElements[] = [$first, 'none'];
            foreach ($blockElementsOptions as $item => $label) {
                $JSBlockElements[] = [$label, $item];
            }
            $jsArray[] = 'RTEarea[editornumber].buttons.formatblock.options = ' . json_encode($JSBlockElements) . ';';
        }
        return implode(LF, $jsArray);
    }
}
