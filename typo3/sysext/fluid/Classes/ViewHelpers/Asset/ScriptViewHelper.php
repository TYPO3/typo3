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
        $this->registerTagAttribute('async', 'bool', 'Define that the script will be fetched in parallel to parsing and evaluation.', false);
        $this->registerTagAttribute('crossorigin', 'string', 'Define how to handle crossorigin requests.', false);
        $this->registerTagAttribute('defer', 'bool', 'Define that the script is meant to be executed after the document has been parsed.', false);
        $this->registerTagAttribute('integrity', 'string', 'Define base64-encoded cryptographic hash of the resource that allows browsers to verify what they fetch.', false);
        $this->registerTagAttribute('nomodule', 'bool', 'Define that the script should not be executed in browsers that support ES2015 modules.', false);
        $this->registerTagAttribute('nonce', 'string', 'Define a cryptographic nonce (number used once) used to whitelist inline styles in a style-src Content-Security-Policy.', false);
        $this->registerTagAttribute('referrerpolicy', 'string', 'Define which referrer is sent when fetching the resource.', false);
        $this->registerTagAttribute('src', 'string', 'Define the URI of the external resource.', false);
        $this->registerTagAttribute('type', 'string', 'Define the MIME type (usually \'text/javascript\').', false);
        $this->registerArgument(
            'identifier',
            'string',
            'Use this identifier within templates to only inject your JS once, even though it is added multiple times.',
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
        $identifier = (string)$this->arguments['identifier'];
        $attributes = $this->tag->getAttributes();

        // boolean attributes shall output attr="attr" if set
        foreach (['async', 'defer', 'nomodule'] as $_attr) {
            if ($attributes[$_attr] ?? false) {
                $attributes[$_attr] = $_attr;
            }
        }

        $src = $this->tag->getAttribute('src');
        unset($attributes['src']);
        $options = [
            'priority' => $this->arguments['priority']
        ];
        if ($src !== null) {
            $this->assetCollector->addJavaScript($identifier, html_entity_decode($src), $attributes, $options);
        } else {
            $content = (string)$this->renderChildren();
            if ($content !== '') {
                $this->assetCollector->addInlineJavaScript($identifier, $content, $attributes, $options);
            }
        }
        return '';
    }
}
