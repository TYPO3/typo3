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

namespace TYPO3\CMS\Core\TypoScript\AST\Merger;

use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\NodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;

/**
 * Frontend TypoScript 'setup' has the main 'config' section for global config,
 * plus a per type / typeNum specific PAGE 'config' (often page.config) that can
 * override global 'config' per type / typeNum.
 *
 * This class merges both into the final 'config', later available in Request
 * attribute 'frontend.typoscript' getConfigTree() and getConfigArray().
 *
 * @internal: Internal AST structure.
 */
final readonly class SetupConfigMerger
{
    public function merge(?ChildNodeInterface $config, ?ChildNodeInterface $pageConfig): RootNode
    {
        $configResult = new RootNode();
        if ($config) {
            foreach ($config->getNextChild() as $child) {
                $configResult->addChild($child);
            }
        }
        if (!$pageConfig) {
            return $configResult;
        }
        $this->mergeRecursive($pageConfig, $configResult);
        return $configResult;
    }

    private function mergeRecursive(ChildNodeInterface $mergeFrom, NodeInterface $mergeTo): void
    {
        foreach ($mergeFrom->getNextChild() as $mergeFromChild) {
            $mergeToChild = $mergeTo->getChildByName($mergeFromChild->getName());
            if (!$mergeToChild) {
                $mergeTo->addChild($mergeFromChild);
                continue;
            }
            $mergeFromChildValue = $mergeFromChild->getValue();
            if ($mergeFromChildValue !== null && $mergeFromChildValue !== $mergeToChild->getValue()) {
                $mergeToChild->setValue($mergeFromChildValue);
            }
            $this->mergeRecursive($mergeFromChild, $mergeToChild);
        }
    }
}
