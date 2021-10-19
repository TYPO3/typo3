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

class t3lib_object_tests_class_with_injectsettings
{
    /**
     * @param \t3lib_object_tests_resolveablecyclic1 $c1
     */
    public function injectFoo(\t3lib_object_tests_resolveablecyclic1 $c1): void
    {
    }

    /**
     * @param \t3lib_object_tests_resolveablecyclic1 $c1
     */
    public function injectingFoo(\t3lib_object_tests_resolveablecyclic1 $c1): void
    {
    }

    /**
     * @param array $settings
     */
    public function injectSettings(array $settings): void
    {
    }
}
