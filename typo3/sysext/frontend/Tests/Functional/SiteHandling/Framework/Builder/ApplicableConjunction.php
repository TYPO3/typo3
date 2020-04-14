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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder;

class ApplicableConjunction implements Applicable
{
    /**
     * @var Applicable[]
     */
    private $applicables;

    public static function create(Applicable ...$applicables): self
    {
        return new static(...$applicables);
    }

    public function __construct(Applicable ...$applicables)
    {
        $this->applicables = $applicables;
    }

    /**
     * @return Applicable[]
     */
    public function getApplicables(): array
    {
        return $this->applicables;
    }

    public function has(string $type): bool
    {
        return !empty($this->filter($type));
    }

    /**
     * @param string $type
     * @return Applicable[]
     */
    public function filter(string $type): array
    {
        return array_filter(
            $this->applicables,
            function (Applicable $applicable) use ($type) {
                return is_a($applicable, $type);
            }
        );
    }

    public function describe(): string
    {
        return sprintf(
            '{%s}',
            implode(' | ', array_map([$this, 'describeItem'], $this->applicables))
        );
    }

    private function describeItem(Applicable $applicable): string
    {
        return $applicable->describe();
    }
}
