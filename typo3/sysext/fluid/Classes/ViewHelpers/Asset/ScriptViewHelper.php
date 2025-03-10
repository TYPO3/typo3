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

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * ViewHelper to add JavaScript to the TYPO3 AssetCollector. Either a file or inline JavaScript can be added.
 *
 * ```
 *    <f:asset.script identifier="identifier123" src="EXT:my_ext/Resources/Public/JavaScript/foo.js" inline="0" />
 *    <f:asset.script identifier="identifier123">
 *       alert('hello world');
 *    </f:asset.script>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-asset-script
 */
final class ScriptViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * This VH does not produce direct output, thus does not need to be wrapped in an escaping node
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Rendered children string is passed as JavaScript code,
     * there is no point in HTML encoding anything from that.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    protected AssetCollector $assetCollector;

    public function injectAssetCollector(AssetCollector $assetCollector): void
    {
        $this->assetCollector = $assetCollector;
    }

    public function initialize(): void
    {
        // Add a tag builder, that does not html encode values, because rendering with encoding happens in AssetRenderer
        $this->setTagBuilder(
            new class () extends TagBuilder {
                public function addAttribute($attributeName, $attributeValue, $escapeSpecialCharacters = false): void
                {
                    parent::addAttribute($attributeName, $attributeValue, false);
                }
            }
        );
        parent::initialize();
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('async', 'bool', 'Define that the script will be fetched in parallel to parsing and evaluation.');
        $this->registerArgument('defer', 'bool', 'Define that the script is meant to be executed after the document has been parsed.');
        $this->registerArgument('nomodule', 'bool', 'Define that the script should not be executed in browsers that support ES2015 modules.');
        $this->registerArgument('useNonce', 'bool', 'Whether to use the global nonce value', false, false);
        $this->registerArgument('identifier', 'string', 'Use this identifier within templates to only inject your JS once, even though it is added multiple times.', true);
        $this->registerArgument('priority', 'boolean', 'Define whether the JavaScript should be put in the <head> tag above-the-fold or somewhere in the body part.', false, false);
        $this->registerArgument('inline', 'bool', 'Define whether or not the referenced file should be loaded as inline script (Only to be used if \'src\' is set).', false, false);
    }

    public function render(): string
    {
        $identifier = (string)$this->arguments['identifier'];
        $attributes = $this->tag->getAttributes();

        // boolean attributes shall output attr="attr" if set
        foreach (['async', 'defer', 'nomodule'] as $attribute) {
            if ($this->arguments[$attribute] ?? false) {
                $attributes[$attribute] = $attribute;
            }
        }

        $src = $attributes['src'] ?? null;
        unset($attributes['src']);
        $options = [
            'priority' => $this->arguments['priority'],
            'useNonce' => $this->arguments['useNonce'],
        ];
        if ($src !== null) {
            if ($this->arguments['inline'] ?? false) {
                $content = @file_get_contents(GeneralUtility::getFileAbsFileName(trim($src)));
                if ($content !== false) {
                    $this->assetCollector->addInlineJavaScript($identifier, $content, $attributes, $options);
                }
            } else {
                $this->assetCollector->addJavaScript($identifier, $src, $attributes, $options);
            }
        } else {
            $content = (string)$this->renderChildren();
            if ($content !== '') {
                $this->assetCollector->addInlineJavaScript($identifier, $content, $attributes, $options);
            }
        }
        return '';
    }
}
