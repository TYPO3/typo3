<?php
declare(strict_types=1);

namespace ExtbaseTeam\B\Domain\Model;

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

/**
 * Class ExtbaseTeam\B\Domain\Model\B
 */
class B extends \ExtbaseTeam\A\Domain\Model\A
{
    /**
     * @var string
     */
    protected $b;

    /**
     * @return string
     */
    public function getB(): string
    {
        return $this->b;
    }

    /**
     * @param string $b
     */
    public function setB(string $b): void
    {
        $this->b = $b;
    }
}
