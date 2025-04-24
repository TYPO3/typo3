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

namespace TYPO3Tests\ActionControllerArgumentTest\Domain\Model;

/**
 * Fixture model data transfer object
 */
class ModelDto
{
    /**
     * @var string
     */
    protected $value;

    /**
     * @var ModelDto
     */
    protected $model;

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getModel(): ModelDto
    {
        return $this->model;
    }

    public function setModel(ModelDto $model): self
    {
        $this->model = $model;
        return $this;
    }
}
