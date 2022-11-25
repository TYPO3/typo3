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

namespace TYPO3\CMS\Redirects\RedirectUpdate;

/**
 * @internal This is a concrete collection implementation and solely used for EXT:redirects and not part of TYPO3's Core API.
 */
final class RedirectSourceCollection implements \Countable
{
    /**
     * @var list<RedirectSourceInterface>
     */
    private array $sources;
    private int $count;

    public function __construct(RedirectSourceInterface ...$sources)
    {
        // Ensure to strip out eventually containing associative keys
        $this->sources = array_values($sources);
        $this->count = count($this->sources);
    }

    /**
     * @return list<RedirectSourceInterface>
     */
    public function all(): array
    {
        return $this->sources;
    }

    public function count(): int
    {
        return $this->count;
    }
}
