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

class t3lib_object_tests_resolveablecyclic2 implements SingletonInterface
{
    public t3lib_object_tests_resolveablecyclic1 $o1;
    public t3lib_object_tests_resolveablecyclic3 $o3;

    /**
     * @param \t3lib_object_tests_resolveablecyclic1 $cyclic1
     */
    public function injectCyclic1(\t3lib_object_tests_resolveablecyclic1 $cyclic1): void
    {
        $this->o1 = $cyclic1;
    }

    /**
     * @param \t3lib_object_tests_resolveablecyclic3 $cyclic3
     */
    public function injectCyclic3(\t3lib_object_tests_resolveablecyclic3 $cyclic3): void
    {
        $this->o3 = $cyclic3;
    }
}
