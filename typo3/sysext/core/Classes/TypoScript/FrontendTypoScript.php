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
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;

/**
 * This class contains the TypoScript set up by the PrepareTypoScriptFrontendRendering
 * Frontend middleware. It can be accessed in content objects:
 *
 * $frontendTypoScript = $request->getAttribute('frontend.typoscript');
 */
final class FrontendTypoScript
{
    private ?RootInclude $setupIncludeTree = null;
    private ?RootNode $setupTree = null;
    private ?array $setupArray = null;
    private ?RootNode $configTree = null;
    private ?array $configArray = null;
    private ?RootNode $pageTree = null;
    private ?array $pageArray = null;

    public function __construct(
        private readonly RootNode $settingsTree,
        private readonly array $settingsConditionList,
        private readonly array $flatSettings,
        private readonly array $setupConditionList,
    ) {}

    /**
     * The settings ("constants") AST.
     *
     * @internal Internal for now until the AST API stabilized.
     */
    public function getSettingsTree(): RootNode
    {
        return $this->settingsTree;
    }

    /**
     * List of settings conditions with verdicts. Used internally for
     * page cache identifier calculation.
     *
     * @internal
     */
    public function getSettingsConditionList(): array
    {
        return $this->settingsConditionList;
    }

    /**
     * This is *always* set up by the middleware / factory: Current settings ("constants")
     * are needed for page cache identifier calculation.
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
     * List of setup conditions with verdicts. Used internally for
     * page cache identifier calculation.
     *
     * @internal
     */
    public function getSetupConditionList(): array
    {
        return $this->setupConditionList;
    }

    /**
     * @internal
     */
    public function setSetupIncludeTree(RootInclude $setupIncludeTree): void
    {
        $this->setupIncludeTree = $setupIncludeTree;
    }

    /**
     * A tree of all TypoScript setup includes. Used internally within
     * FrontendTypoScriptFactory to suppress calculating the include tree
     * twice.
     *
     * @internal
     */
    public function getSetupIncludeTree(): ?RootInclude
    {
        return $this->setupIncludeTree;
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
     * Frontend TypoScript setup is not calculated, AST and the array representation aren't set.
     * Calling getSetupTree() or getSetupArray() will then throw an exception.
     *
     * To avoid the exception, consumers can call hasSetup() beforehand.
     *
     * Note casual content objects do not need to do this, since setup TypoScript is always
     * set up when content objects need to be calculated.
     *
     * @internal
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
     * can not get the *full* content from page cache. This is the case when a page cache entry does
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
     * The merged TypoScript 'config.'.
     *
     * This is the result of the "global" TypoScript 'config' section, merged with
     * the 'config' section of the determined PAGE object which can override
     * "global" 'config' per type / typeNum.
     *
     * This is *always* needed within casual Frontend rendering by FrontendTypoScriptFactory and
     * has a dedicated cache layer to be quick to retrieve. It is needed even in fully cached pages
     * context to for instance know if debug headers should be added ("config.debug=1") to a response.
     *
     * @internal Internal for now until the AST API stabilized.
     */
    public function getConfigTree(): RootNode
    {
        if ($this->configTree === null) {
            throw new \RuntimeException(
                'Setup "config." not initialized. FrontendTypoScriptFactory->createSetupConfigOrFullSetup() not called?',
                1710666154
            );
        }
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
     * Array representation of getConfigTree().
     */
    public function getConfigArray(): array
    {
        if ($this->configArray === null) {
            throw new \RuntimeException(
                'Setup "config." not initialized. FrontendTypoScriptFactory->createSetupConfigOrFullSetup() not called?',
                1710666123
            );
        }
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
     * The determined PAGE object from main TypoScript 'setup' that depends
     * on type / typeNum.
     *
     * This is used internally by RequestHandler for page generation.
     * It is *not* set in full cached page scenarios without _INT object.
     *
     * @internal
     */
    public function getPageTree(): RootNode
    {
        if ($this->pageTree === null) {
            throw new \RuntimeException(
                'PAGE node has not been initialized. This happens in cached Frontend scope where full TypoScript' .
                ' is not needed by the system, and if a PAGE object for given type could not be determined.' .
                ' Test with hasPage().',
                1710399966
            );
        }
        return $this->pageTree;
    }

    /**
     * @internal
     */
    public function hasPage(): bool
    {
        return $this->pageTree !== null;
    }

    /**
     * @internal
     */
    public function setPageArray(array $pageArray): void
    {
        $this->pageArray = $pageArray;
    }

    /**
     * Array representation of getPageTree().
     *
     * @internal
     */
    public function getPageArray(): array
    {
        if ($this->pageArray === null) {
            throw new \RuntimeException(
                'PAGE array has not been initialized. This happens in cached Frontend scope where full TypoScript' .
                ' is not needed by the system, and if a PAGE object for given type could not be determined.' .
                ' Test with hasPage().',
                1710399967
            );
        }
        return $this->pageArray;
    }
}
