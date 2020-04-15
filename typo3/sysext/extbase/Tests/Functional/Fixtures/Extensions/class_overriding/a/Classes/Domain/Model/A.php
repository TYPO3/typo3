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

namespace ExtbaseTeam\A\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class ExtbaseTeam\A\Domain\Model\A
 */
class A extends AbstractEntity
{
    /**
     * @var string
     */
    protected $a;

    /**
     * @return string
     */
    public function getA(): string
    {
        return $this->a;
    }

    /**
     * @param string $a
     */
    public function setA(string $a): void
    {
        $this->a = $a;
    }
}
