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
 * test class A that depends on B and C and has a third default parameter in constructor
 */
class t3lib_object_tests_amixed_array_singleton implements SingletonInterface
{
    public t3lib_object_tests_b $b;
    public t3lib_object_tests_c $c;
    public array $myvalue;

    /**
     * @param \t3lib_object_tests_b $b
     * @param \t3lib_object_tests_c $c
     * @param array $someDefaultParameter
     */
    public function __construct(\t3lib_object_tests_b $b, \t3lib_object_tests_c $c, array $someDefaultParameter = ['some' => 'default'])
    {
        $this->b = $b;
        $this->c = $c;
        $this->myvalue = $someDefaultParameter;
    }
}
