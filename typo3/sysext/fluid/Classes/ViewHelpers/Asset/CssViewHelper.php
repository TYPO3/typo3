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

/**
 * CssViewHelper
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
 */
class CssViewHelper extends AbstractTagBasedViewHelper
{

    /**
     * @var AssetCollector
     */
    protected $assetCollector;

    /**
     * @param AssetCollector $assetCollector
     */
    public function injectAssetCollector(AssetCollector $assetCollector): void
    {
        $this->assetCollector = $assetCollector;
    }

    /**
     * @api
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        parent::registerUniversalTagAttributes();
        $this->registerTagAttribute('as', 'string', '', false);
        $this->registerTagAttribute('crossorigin', 'string', '', false);
        $this->registerTagAttribute('disabled', 'bool', '', false);
        $this->registerTagAttribute('href', 'string', '', false);
        $this->registerTagAttribute('hreflang', 'string', '', false);
        $this->registerTagAttribute('importance', 'string', '', false);
        $this->registerTagAttribute('integrity', 'string', '', false);
        $this->registerTagAttribute('media', 'string', '', false);
        $this->registerTagAttribute('referrerpolicy', 'string', '', false);
        $this->registerTagAttribute('rel', 'string', '', false);
        $this->registerTagAttribute('sizes', 'string', '', false);
        $this->registerTagAttribute('type', 'string', '', false);
        $this->registerTagAttribute('nonce', 'string', '', false);
        $this->registerArgument(
            'identifier',
            'string',
            'Use this identifier within templates to only inject your CSS once, even though it is added multiple times',
            true
        );
        $this->registerArgument(
            'priority',
            'boolean',
            'Define whether the css should be put in the <head> tag above-the-fold or somewhere in the body part.',
            false,
            false
        );
    }

    public function render(): string
    {
        $identifier = (string)$this->arguments['identifier'];
        $attributes = $this->tag->getAttributes();

        // boolean attributes shall output attr="attr" if set
        if ($attributes['disabled'] ?? false) {
            $attributes['disabled'] = 'disabled';
        }

        $file = $this->tag->getAttribute('href');
        unset($attributes['href']);
        $options = [
            'priority' => $this->arguments['priority']
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
