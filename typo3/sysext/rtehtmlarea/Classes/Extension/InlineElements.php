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
 * InlineElements plugin for htmlArea RTE
 */
class InlineElements extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'InlineElements';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'formattext, bidioverride, big, bold, citation, code, definition, deletedtext, emphasis, insertedtext, italic, keyboard, quotation, sample, small, span, strikethrough, strong, subscript, superscript, underline, variable';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'formattext' => 'FormatText',
        'bidioverride' => 'BiDiOverride',
        'big' => 'Big',
        'bold' => 'Bold',
        'citation' => 'Citation',
        'code' => 'Code',
        'definition' => 'Definition',
        'deletedtext' => 'DeletedText',
        'emphasis' => 'Emphasis',
        'insertedtext' => 'InsertedText',
        'italic' => 'Italic',
        'keyboard' => 'Keyboard',
        'monospaced' => 'MonoSpaced',
        'quotation' => 'Quotation',
        'sample' => 'Sample',
        'small' => 'Small',
        'span' => 'Span',
        'strikethrough' => 'StrikeThrough',
        'strong' => 'Strong',
        'subscript' => 'Subscript',
        'superscript' => 'Superscript',
        'underline' => 'Underline',
        'variable' => 'Variable'
    ];

    /**
     * Default list of inline elements
     *
     * @var array
     */
    protected $defaultInlineElements = [
        'none' => 'No markup',
        'b' => 'Bold',
        'bdo' => 'BiDi override',
        'big' => 'Large text',
        'cite' => 'Citation',
        'code' => 'Code',
        'del' => 'Deleted text',
        'dfn' => 'Definition',
        'em' => 'Emphasis',
        'i' => 'Italic',
        'ins' => 'Inserted text',
        'kbd' => 'Keyboard',
        'q' => 'Quotation',
        'samp' => 'Sample',
        'small' => 'Small text',
        'span' => 'Style container',
        'strike' => 'Strike-through',
        'strong' => 'Strong emphasis',
        'sub' => 'Subscript',
        'sup' => 'Superscript',
        'tt' => 'Monospaced text',
        'u' => 'Underline',
        'var' => 'Variable'
    ];

    /**
     * Default order of inline elements
     *
     * @var string
     */
    protected $defaultInlineElementsOrder = 'none, bidioverride, big, bold, citation, code, definition, deletedtext, emphasis, insertedtext, italic, keyboard,
		monospaced, quotation, sample, small, span, strikethrough, strong, subscript, superscript, underline, variable';

    /**
     * Button names to inline elements
     *
     * @var array
     */
    protected $buttonToInlineElement = [
        'none' => 'none',
        'bidioverride' => 'bdo',
        'big' => 'big',
        'bold' => 'b',
        'citation' => 'cite',
        'code' => 'code',
        'definition' => 'dfn',
        'deletedtext' => 'del',
        'emphasis' => 'em',
        'insertedtext' => 'ins',
        'italic' => 'i',
        'keyboard' => 'kbd',
        'monospaced' => 'tt',
        'quotation' => 'q',
        'sample' => 'samp',
        'small' => 'small',
        'span' => 'span',
        'strikethrough' => 'strike',
        'strong' => 'strong',
        'subscript' => 'sub',
        'superscript' => 'sup',
        'underline' => 'u',
        'variable' => 'var'
    ];

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins
     */
    public function buildJavascriptConfiguration()
    {
        $jsArray = [];
        if (in_array('formattext', $this->toolbar)) {
            if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.']['formattext.'])) {
                $jsArray[] = 'RTEarea[editornumber].buttons.formattext = new Object();';
            }
            // Default inline elements
            $hideItems = [];
            $restrictTo = ['*'];
            $inlineElementsOrder = $this->defaultInlineElementsOrder;
            $prefixLabelWithTag = false;
            $postfixLabelWithTag = false;
            // Processing PageTSConfig
            if (is_array($this->configuration['thisConfig']['buttons.']) && is_array($this->configuration['thisConfig']['buttons.']['formattext.'])) {
                // Removing elements
                if ($this->configuration['thisConfig']['buttons.']['formattext.']['removeItems']) {
                    $hideItems = GeneralUtility::trimExplode(',', $this->cleanList($this->configuration['thisConfig']['buttons.']['formattext.']['removeItems']), true);
                }
                // Restriction clause
                if ($this->configuration['thisConfig']['buttons.']['formattext.']['restrictTo']) {
                    $restrictTo = GeneralUtility::trimExplode(',', $this->cleanList('none,' . $this->configuration['thisConfig']['buttons.']['formattext.']['restrictTo']), true);
                } elseif ($this->configuration['thisConfig']['buttons.']['formattext.']['restrictToItems']) {
                    $restrictTo = GeneralUtility::trimExplode(',', $this->cleanList('none,' . $this->configuration['thisConfig']['buttons.']['formattext.']['restrictToItems']), true);
                }
                // Elements order
                if ($this->configuration['thisConfig']['buttons.']['formattext.']['orderItems']) {
                    $inlineElementsOrder = 'none,' . $this->configuration['thisConfig']['buttons.']['formattext.']['orderItems'];
                }
                $prefixLabelWithTag = $this->configuration['thisConfig']['buttons.']['formattext.']['prefixLabelWithTag'] ? true : $prefixLabelWithTag;
                $postfixLabelWithTag = $this->configuration['thisConfig']['buttons.']['formattext.']['postfixLabelWithTag'] ? true : $postfixLabelWithTag;
            }
            $inlineElementsOrder = array_diff(GeneralUtility::trimExplode(',', $this->cleanList($inlineElementsOrder), true), $hideItems);
            if (!in_array('*', $restrictTo)) {
                $inlineElementsOrder = array_intersect($inlineElementsOrder, $restrictTo);
            }
            // Localizing the options
            $inlineElementsOptions = [];
            foreach ($inlineElementsOrder as $item) {
                $inlineElementsOptions[$this->buttonToInlineElement[$item]] = $this->getLanguageService()->sL(
                    'LLL:EXT:rtehtmlarea/Resources/Private/Language/Plugins/InlineElements/locallang.xlf:' . $this->defaultInlineElements[$this->buttonToInlineElement[$item]]
                );
                $inlineElementsOptions[$this->buttonToInlineElement[$item]] = ($prefixLabelWithTag && $item != 'none' ? $this->buttonToInlineElement[$item] . ' - ' : '') . $inlineElementsOptions[$this->buttonToInlineElement[$item]] . ($postfixLabelWithTag && $item != 'none' ? ' - ' . $this->buttonToInlineElement[$item] : '');
            }
            $first = array_shift($inlineElementsOptions);
            // Sorting the options
            if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.']['formattext.']) || !$this->configuration['thisConfig']['buttons.']['formattext.']['orderItems']) {
                asort($inlineElementsOptions);
            }
            // Generating the javascript options
            $JSInlineElements = [];
            $JSInlineElements[] = [$first, 'none'];
            foreach ($inlineElementsOptions as $item => $label) {
                $JSInlineElements[] = [$label, $item];
            }
            $jsArray[] = 'RTEarea[editornumber].buttons.formattext.options = ' . json_encode($JSInlineElements) . ';';
        }
        return implode(LF, $jsArray);
    }
}
