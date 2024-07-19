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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * ViewHelper to add CSS to the TYPO3 AssetCollector. Either a file or inline CSS can be added.
 *
 * Examples
 * ========
 *
 * ::
 *
 *    <f:asset.css identifier="identifier123" href="EXT:my_ext/Resources/Public/Css/foo.css" />
 *    <f:asset.css identifier="identifier123">
 *       .foo { color: black; }
 *    </f:asset.css>
 *
 * Details
 * =======
 *
 * In the AssetCollector, the "identifier" attribute is used as a unique identifier. Thus, if assets are added multiple
 * times using the same identifier, the asset will only be served once (the last added overrides previous assets).
 *
 * Some available attributes are defaults but do not make sense for this ViewHelper. Relevant attributes specific
 * for this ViewHelper are: as, crossorigin, disabled, href, hreflang, importance, integrity, media, referrerpolicy,
 * sizes, type, nonce.
 */
final class CssViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * This VH does not produce direct output, thus does not need to be wrapped in an escaping node
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Rendered children string is passed as CSS code,
     * there is no point in HTML encoding anything from that.
     *
     * @var bool
     */
    protected $escapeChildren = true;

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
        $this->registerArgument('disabled', 'bool', 'Define whether or not the described stylesheet should be loaded and applied to the document.');
        $this->registerArgument('useNonce', 'bool', 'Whether to use the global nonce value', false, false);
        $this->registerArgument('identifier', 'string', 'Use this identifier within templates to only inject your CSS once, even though it is added multiple times.', true);
        $this->registerArgument('priority', 'boolean', 'Define whether the CSS should be included before other CSS. CSS will always be output in the <head> tag.', false, false);
    }

    public function render(): string
    {
        $identifier = (string)$this->arguments['identifier'];
        $attributes = $this->tag->getAttributes();

        // boolean attributes shall output attr="attr" if set
        if ($this->arguments['disabled'] ?? false) {
            $attributes['disabled'] = 'disabled';
        }

        $file = $attributes['href'] ?? null;
        unset($attributes['href']);
        $options = [
            'priority' => $this->arguments['priority'],
            'useNonce' => $this->arguments['useNonce'],
        ];
        if ($file !== null) {
            $this->assetCollector->addStyleSheet($identifier, $file, $attributes, $options);
        } else {
            $content = (string)$this->renderChildren();
            if ($content !== '') {
                $this->assetCollector->addInlineStyleSheet($identifier, $content, $attributes, $options);
            }
        }
        return '';
    }
}
