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

namespace TYPO3\CMS\Filelist\Matcher;

/**
 * @internal
 */
final readonly class AndMatcher implements MatcherInterface
{
    /**
     * @var MatcherInterface[]
     */
    private array $matchers;

    public function __construct(MatcherInterface ...$matchers)
    {
        $this->matchers = $matchers;
    }

    public function supports(mixed $item): bool
    {
        if ($this->matchers === []) {
            return false;
        }

        foreach ($this->matchers as $matcher) {
            if (!$matcher->supports($item)) {
                return false;
            }
        }

        return true;
    }

    public function match(mixed $item): bool
    {
        if ($this->matchers === []) {
            return false;
        }

        foreach ($this->matchers as $matcher) {
            if (!$matcher->match($item)) {
                return false;
            }
        }

        return true;
    }
}
