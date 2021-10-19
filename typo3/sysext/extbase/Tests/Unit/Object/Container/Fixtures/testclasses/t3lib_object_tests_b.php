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

use TYPO3\CMS\Core\SingletonInterface;

/**
 * test class B that depends on C
 */
class t3lib_object_tests_b implements SingletonInterface
{
    public t3lib_object_tests_c $c;

    /**
     * @param \t3lib_object_tests_c $c
     */
    public function __construct(\t3lib_object_tests_c $c)
    {
        $this->c = $c;
    }
}
