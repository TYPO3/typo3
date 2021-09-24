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

namespace TYPO3\CMS\Core\Page;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * The Asset Collector is responsible for keeping track of
 * - everything within <script> tags: javascript files and inline javascript code
 * - inline CSS and CSS files
 *
 * The goal of the asset collector is to:
 * - utilize a single "runtime-based" store for adding assets of certain kinds that are added to the output
 * - allow to deal with assets from non-cacheable plugins and cacheable content in the Frontend
 * - reduce the "power" and flexibility (I'd say it's a burden) of the "god class" PageRenderer.
 * - reduce the burden of storing everything in PageRenderer
 *
 * As a side-effect this allows to:
 * - Add a single CSS snippet or CSS file per content block, but assure that the CSS is only added once to the output.
 *
 * Note on the implementation:
 * - We use a Singleton to make use of the AssetCollector throughout Frontend process (similar to PageRenderer).
 * - Although this is not optimal, I don't see any other way to do so in the current code.
 *
 * https://developer.wordpress.org/reference/functions/wp_enqueue_style/
 */
class AssetCollector implements SingletonInterface
{
    /**
     * @var array
     */
    protected $javaScripts = [];

    /**
     * @var array
     */
    protected $inlineJavaScripts = [];

    /**
     * @var array
     */
    protected $styleSheets = [];

    /**
     * @var array
     */
    protected $inlineStyleSheets = [];

    /**
     * @var array
     */
    protected $media = [];

    /**
     * @param string $identifier
     * @param string $source URI to JavaScript file (allows EXT: syntax)
     * @param array $attributes additional HTML <script> tag attributes
     * @param array $options ['priority' => true] means rendering before other tags
     * @return AssetCollector
     */
    public function addJavaScript(string $identifier, string $source, array $attributes = [], array $options = []): self
    {
        $existingAttributes = $this->javaScripts[$identifier]['attributes'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingAttributes, $attributes);
        $existingOptions = $this->javaScripts[$identifier]['options'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingOptions, $options);
        $this->javaScripts[$identifier] = [
            'source' => $source,
            'attributes' => $existingAttributes,
            'options' => $existingOptions,
        ];
        return $this;
    }

    /**
     * @param string $identifier
     * @param string $source JavaScript code
     * @param array $attributes additional HTML <script> tag attributes
     * @param array $options ['priority' => true] means rendering before other tags
     * @return AssetCollector
     */
    public function addInlineJavaScript(string $identifier, string $source, array $attributes = [], array $options = []): self
    {
        $existingAttributes = $this->inlineJavaScripts[$identifier]['attributes'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingAttributes, $attributes);
        $existingOptions = $this->inlineJavaScripts[$identifier]['options'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingOptions, $options);
        $this->inlineJavaScripts[$identifier] = [
            'source' => $source,
            'attributes' => $existingAttributes,
            'options' => $existingOptions,
        ];
        return $this;
    }

    /**
     * @param string $identifier
     * @param string $source URI to stylesheet file (allows EXT: syntax)
     * @param array $attributes additional HTML <link> tag attributes
     * @param array $options ['priority' => true] means rendering before other tags
     * @return AssetCollector
     */
    public function addStyleSheet(string $identifier, string $source, array $attributes = [], array $options = []): self
    {
        $existingAttributes = $this->styleSheets[$identifier]['attributes'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingAttributes, $attributes);
        $existingOptions = $this->styleSheets[$identifier]['options'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingOptions, $options);
        $this->styleSheets[$identifier] = [
            'source' => $source,
            'attributes' => $existingAttributes,
            'options' => $existingOptions,
        ];
        return $this;
    }

    /**
     * @param string $identifier
     * @param string $source stylesheet code
     * @param array $attributes additional HTML <link> tag attributes
     * @param array $options ['priority' => true] means rendering before other tags
     * @return AssetCollector
     */
    public function addInlineStyleSheet(string $identifier, string $source, array $attributes = [], array $options = []): self
    {
        $existingAttributes = $this->inlineStyleSheets[$identifier]['attributes'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingAttributes, $attributes);
        $existingOptions = $this->inlineStyleSheets[$identifier]['options'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingOptions, $options);
        $this->inlineStyleSheets[$identifier] = [
            'source' => $source,
            'attributes' => $existingAttributes,
            'options' => $existingOptions,
        ];
        return $this;
    }

    /**
     * @param string $fileName
     * @param array $additionalInformation One dimensional hash map (array with non numerical keys) with scalar values
     * @return AssetCollector
     */
    public function addMedia(string $fileName, array $additionalInformation): self
    {
        $existingAdditionalInformation = $this->media[$fileName] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($existingAdditionalInformation, $this->ensureAllValuesAreSerializable($additionalInformation));
        $this->media[$fileName] = $existingAdditionalInformation;
        return $this;
    }

    private function ensureAllValuesAreSerializable(array $additionalInformation): array
    {
        // Currently just filtering all non scalar values
        return array_filter($additionalInformation, 'is_scalar');
    }

    public function removeJavaScript(string $identifier): self
    {
        unset($this->javaScripts[$identifier]);
        return $this;
    }

    public function removeInlineJavaScript(string $identifier): self
    {
        unset($this->inlineJavaScripts[$identifier]);
        return $this;
    }

    public function removeStyleSheet(string $identifier): self
    {
        unset($this->styleSheets[$identifier]);
        return $this;
    }

    public function removeInlineStyleSheet(string $identifier): self
    {
        unset($this->inlineStyleSheets[$identifier]);
        return $this;
    }

    public function removeMedia(string $identifier): self
    {
        unset($this->media[$identifier]);
        return $this;
    }

    public function getMedia(): array
    {
        return $this->media;
    }

    public function getJavaScripts(?bool $priority = null): array
    {
        return $this->filterAssetsPriority($this->javaScripts, $priority);
    }

    public function getInlineJavaScripts(?bool $priority = null): array
    {
        return $this->filterAssetsPriority($this->inlineJavaScripts, $priority);
    }

    public function getStyleSheets(?bool $priority = null): array
    {
        return $this->filterAssetsPriority($this->styleSheets, $priority);
    }

    public function getInlineStyleSheets(?bool $priority = null): array
    {
        return $this->filterAssetsPriority($this->inlineStyleSheets, $priority);
    }

    /**
     * @param array $assets Takes a reference to prevent a deep copy. The variable is not changed (const).
     * @param bool|null $priority null: no filter; else filters for the given priority
     * @return array
     */
    protected function filterAssetsPriority(array &$assets, ?bool $priority): array
    {
        if ($priority === null) {
            return $assets;
        }
        $currentPriorityAssets = [];
        foreach ($assets as $identifier => $asset) {
            if ($priority === ($asset['options']['priority'] ?? false)) {
                $currentPriorityAssets[$identifier] = $asset;
            }
        }
        return $currentPriorityAssets;
    }

    /**
     * @param array $newState
     * @internal
     */
    public function updateState(array $newState): void
    {
        foreach ($newState as $var => $value) {
            $this->{$var} = $value;
        }
    }

    /**
     * @return array
     * @internal
     */
    public function getState(): array
    {
        $state = [];
        foreach (get_object_vars($this) as $var => $value) {
            $state[$var] = $value;
        }
        return $state;
    }
}
