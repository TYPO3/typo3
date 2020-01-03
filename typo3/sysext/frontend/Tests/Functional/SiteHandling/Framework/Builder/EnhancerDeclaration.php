<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder;

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

class EnhancerDeclaration implements Applicable, HasGenerateParameters, HasResolveArguments
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var array
     */
    private $configuration = [];

    /**
     * @var array
     */
    private $resolveArguments = [];

    /**
     * @var array
     */
    private $generateParameters = [];

    public static function create(string $identifier): self
    {
        return new static($identifier);
    }

    private function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return mixed
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @return array
     */
    public function getResolveArguments(): array
    {
        return $this->resolveArguments;
    }

    /**
     * @return array
     */
    public function getGenerateParameters(): array
    {
        return $this->generateParameters;
    }

    public function withConfiguration(array $configuration): self
    {
        $target = clone $this;
        $target->configuration = $configuration;
        return $target;
    }

    public function withResolveArguments(array $resolveArguments): self
    {
        $target = clone $this;
        $target->resolveArguments = $resolveArguments;
        return $target;
    }

    public function withGenerateParameters(array $generateParameters): self
    {
        $target = clone $this;
        $target->generateParameters = $generateParameters;
        return $target;
    }

    public function describe(): string
    {
        return $this->identifier;
    }
}
