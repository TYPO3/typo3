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

namespace TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath;

/**
 * A stack for CurrentObjectPath: When opening a block "{",
 * CurrentObjectPath is pushed, when closing a block "}", it
 * is popped from this stack.
 *
 * @internal: Internal AST structure.
 */
final class CurrentObjectPathStack
{
    /**
     * @var CurrentObjectPath[]
     */
    private array $stack = [];
    private int $stackSize = 0;

    public function push(CurrentObjectPath $path): void
    {
        $this->stack[] = $path;
        $this->stackSize ++;
    }

    public function pop(): CurrentObjectPath
    {
        if ($this->stackSize === 1) {
            // Never pop the very last element off from the stack. This is the
            // RootNode. This prevents errors when TypoScript has a closing
            // curly bracket '}' too much.
            return $this->getCurrent();
        }
        array_pop($this->stack);
        $this->stackSize --;
        return $this->getCurrent();
    }

    public function getCurrent(): CurrentObjectPath
    {
        return $this->stack[array_key_last($this->stack)];
    }

    public function getFirst(): CurrentObjectPath
    {
        return reset($this->stack);
    }
}
