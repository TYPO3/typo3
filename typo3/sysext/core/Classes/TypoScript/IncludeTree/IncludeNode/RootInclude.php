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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode;

/**
 * Root of the IncludeTree. Does not contain LineStreams itself,
 * only children do.
 *
 * @internal: Internal tree structure.
 */
final class RootInclude extends AbstractInclude
{
    protected string $name = 'ROOT';

    public function setName(string $name): void
    {
        throw new \LogicException('Can not set name on RootNode', 1656668001);
    }
}
