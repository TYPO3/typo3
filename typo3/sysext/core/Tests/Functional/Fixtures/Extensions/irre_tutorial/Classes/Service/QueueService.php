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

namespace OliverHader\IrreTutorial\Service;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * ContentController
 */
class QueueService implements SingletonInterface
{
    /**
     * @var array
     */
    protected $calls;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var bool
     */
    protected $active = false;

    /**
     * @param array $calls
     */
    public function set(array $calls): void
    {
        $this->calls = $calls;
        $this->active = true;
    }

    /**
     * @return array
     */
    public function get(): array
    {
        return $this->calls;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive($active = true): void
    {
        $this->active = (bool)$active;
    }

    /**
     * @return array|null
     */
    public function shift(): ?array
    {
        return array_shift($this->calls);
    }

    /**
     * @param string $identifier
     * @param mixed $value
     */
    public function addValue($identifier, $value): void
    {
        $this->values[$identifier] = $value;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
