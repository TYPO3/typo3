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

namespace TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures;

/**
 * Fixture constructor two parameter test
 */
class TwoParametersConstructorFixture
{
    public ?string $constructorParameter1;
    public ?string $constructorParameter2;

    /**
     * @param string $parameter1
     * @param string $parameter2
     */
    public function __construct(string $parameter1, string $parameter2)
    {
        $this->constructorParameter1 = $parameter1;
        $this->constructorParameter2 = $parameter2;
    }
}
