<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Fluid\ViewHelpers\Asset;

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

use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ScriptViewHelper
 *
 * Examples
 * ========
 *
 * ::
 *
 *    <f:asset.script identifier="identifier123" src="EXT:my_ext/Resources/Public/JavaScript/foo.js" />
 *    <f:asset.script identifier="identifier123">
 *       alert('hello world');
 *    </f:asset.script>
 */
class ScriptViewHelper extends AbstractTagBasedViewHelper
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
        $this->registerTagAttribute('async', 'string', '', false);
        $this->registerTagAttribute('crossorigin', 'string', '', false);
        $this->registerTagAttribute('defer', 'string', '', false);
        $this->registerTagAttribute('integrity', 'string', '', false);
        $this->registerTagAttribute('nomodule', 'string', '', false);
        $this->registerTagAttribute('nonce', 'string', '', false);
        $this->registerTagAttribute('referrerpolicy', 'string', '', false);
        $this->registerTagAttribute('src', 'string', '', false);
        $this->registerTagAttribute('type', 'string', '', false);
        $this->registerArgument(
            'identifier',
            'string',
            'Use this identifier within templates to only inject your JS once, even though it is added multiple times',
            true
        );
        $this->registerArgument(
            'priority',
            'boolean',
            'Define whether the JavaScript should be put in the <head> tag above-the-fold or somewhere in the body part.',
            false,
            false
        );
    }

    public function render(): string
    {
        $identifier = $this->arguments['identifier'];
        $attributes = $this->tag->getAttributes();
        $src = $this->tag->getAttribute('src');
        unset($attributes['src']);
        $options = [
            'priority' => $this->arguments['priority']
        ];
        if ($src !== null) {
            $this->assetCollector->addJavaScript($identifier, $src, $attributes, $options);
        } else {
            $this->assetCollector->addInlineJavaScript($identifier, $this->renderChildren(), $attributes, $options);
        }
        return '';
    }
}
