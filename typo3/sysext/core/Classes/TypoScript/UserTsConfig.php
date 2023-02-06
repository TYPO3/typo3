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
 * A data object that carries the final user TSconfig. This is created by UserTsConfigFactory.
 *
 * @internal Internal for now until API stabilized. Use backendUser->getTSConfig().
 */
final class UserTsConfig
{
    private readonly array $userTsConfigArray;

    public function __construct(
        private readonly RootNode $userTsConfigTree
    ) {
        $this->userTsConfigArray = $userTsConfigTree->toArray();
    }

    public function getUserTsConfigTree(): RootNode
    {
        return $this->userTsConfigTree;
    }

    public function getUserTsConfigArray(): array
    {
        return $this->userTsConfigArray;
    }
}
