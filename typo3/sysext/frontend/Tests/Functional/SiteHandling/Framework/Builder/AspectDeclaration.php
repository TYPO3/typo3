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

class AspectDeclaration implements Applicable
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var array
     */
    private $configuration = [];

    public static function create(string $identifier): self
    {
        return new static($identifier);
    }

    private function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function withConfiguration(array $configuration): self
    {
        $target = clone $this;
        $target->configuration = $configuration;
        return $target;
    }

    public function describe(): string
    {
        return $this->identifier;
    }
}
