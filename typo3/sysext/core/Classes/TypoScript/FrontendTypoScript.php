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

namespace TYPO3\CMS\Core\TypoScript;

use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;

/**
 * This class contains the TypoScript set up by the PrepareTypoScriptFrontendRendering
 * Frontend middleware. It can be accessed in content objects:
 *
 * $frontendTypoScript = $request->getAttribute('frontend.typoscript');
 */
final class FrontendTypoScript
{
    private RootNode|null $setupTree = null;
    private array|null $setupArray = null;
    private RootNode $configTree;
    private array $configArray;
    private RootNode $pageTree;
    private array $pageArray;

    public function __construct(
        private readonly RootNode $settingsTree,
        private readonly array $flatSettings,
    ) {}

    /**
     * @internal Internal for now until the AST API stabilized.
     */
    public function getSettingsTree(): RootNode
    {
        return $this->settingsTree;
    }

    /**
     * This is *always* set up by the middleware: Current settings (aka "TypoScript constants")
     * are needed for page cache identifier calculation.
     *
     * This is a "flattened" array of all settings, as example, consider these settings TypoScript:
     *
     * ```
     * mySettings {
     *     foo = fooValue
     *     bar = barValue
     * }
     * ```
     *
     * This will result in this array:
     *
     * ```
     * $flatSettings = [
     *     'mySettings.foo' => 'fooValue',
     *     'mySettings.bar' => 'barValue',
     * ];
     * ```
     */
    public function getFlatSettings(): array
    {
        return $this->flatSettings;
    }

    /**
     * @internal
     */
    public function setSetupTree(RootNode $setupTree): void
    {
        $this->setupTree = $setupTree;
    }

    /**
     * When a page is retrieved from cache and does not contain COA_INT or USER_INT objects,
     * Frontend TypoScript setup is not calculated, so the AST and the array are not set.
     * Calling getSetupTree() or getSetupArray() will then throw an exception.
     *
     * To avoid the exception, consumers can call hasSetup() beforehand.
     *
     * Note casual content objects do not need to do this, since setup TypoScript is always
     * set up when content objects need to be calculated.
     */
    public function hasSetup(): bool
    {
        return $this->setupTree !== null;
    }

    /**
     * @internal Internal for now until the AST API stabilized.
     */
    public function getSetupTree(): RootNode
    {
        if ($this->setupTree === null) {
            throw new \RuntimeException(
                'Setup tree has not been initialized. This happens in cached Frontend scope where full TypoScript' .
                ' is not needed by the system.',
                1666513644
            );
        }
        return $this->setupTree;
    }

    /**
     * @internal
     */
    public function setSetupArray(array $setupArray): void
    {
        $this->setupArray = $setupArray;
    }

    /**
     * The full Frontend TypoScript array.
     *
     * This is always set up as soon as the Frontend rendering needs to actually render something and
     * can not get the full content from page cache. This is the case when a page cache entry does
     * not exist, or when the page contains COA_INT or USER_INT objects.
     */
    public function getSetupArray(): array
    {
        if ($this->setupArray === null) {
            throw new \RuntimeException(
                'Setup array has not been initialized. This happens in cached Frontend scope where full TypoScript' .
                ' is not needed by the system.',
                1666513645
            );
        }
        return $this->setupArray;
    }

    /**
     * @internal
     */
    public function setConfigTree(RootNode $setupConfig): void
    {
        $this->configTree = $setupConfig;
    }

    /**
     * @internal
     */
    public function getConfigTree(): RootNode
    {
        return $this->configTree;
    }

    /**
     * @internal
     */
    public function setConfigArray(array $configArray): void
    {
        $this->configArray = $configArray;
    }

    /**
     * The merged TypoScript 'config'.
     *
     * This is the result of the "global" TypoScript 'config' section, merged with
     * the 'config' section of the determined PAGE object which can override
     * "global" 'config' per type / typeNum.
     */
    public function getConfigArray(): array
    {
        return $this->configArray;
    }

    /**
     * @internal
     */
    public function setPageTree(RootNode $pageTree): void
    {
        $this->pageTree = $pageTree;
    }

    /**
     * @internal
     */
    public function getPageTree(): RootNode
    {
        return $this->pageTree;
    }

    /**
     * @internal
     */
    public function setPageArray(array $pageArray): void
    {
        $this->pageArray = $pageArray;
    }

    /**
     * The determined PAGE object from main TypoScript 'setup' that depends
     * on type / typeNum.
     *
     * This is used internally by RequestHandler for page generation.
     * It is *not* set in full cached page scenarios without _INT object.
     *
     * *If* this in made non-internal, a method "hasPage()" should be added
     * for extensions to verify if page is actually set.
     *
     * @internal
     */
    public function getPageArray(): array
    {
        return $this->pageArray;
    }
}
