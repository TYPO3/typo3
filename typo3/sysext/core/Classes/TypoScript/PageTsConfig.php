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
 * A data object that carries the final page TSconfig. This is created by PageTsConfigFactory.
 *
 * @internal Internal for now until API stabilized. Use BackendUtility::getPagesTSconfig().
 */
final class PageTsConfig
{
    private readonly array $pageTsConfigArray;

    public function __construct(
        private readonly RootNode $pageTsConfigTree
    ) {
        $this->pageTsConfigArray = $pageTsConfigTree->toArray();
    }

    public function getPageTsConfigTree(): RootNode
    {
        return $this->pageTsConfigTree;
    }

    public function getPageTsConfigArray(): array
    {
        return $this->pageTsConfigArray;
    }
}
