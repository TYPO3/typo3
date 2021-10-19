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

/**
 * class which has setter injections defined
 */
class t3lib_object_tests_injectmethods
{
    public t3lib_object_tests_b $b;
    public t3lib_object_tests_b_child $bchild;

    /**
     * @param \t3lib_object_tests_b $o
     */
    public function injectClassB(\t3lib_object_tests_b $o): void
    {
        $this->b = $o;
    }

    /**
     * @TYPO3\CMS\Extbase\Annotation\Inject
     * @param \t3lib_object_tests_b_child $o
     */
    public function setClassBChild(\t3lib_object_tests_b_child $o): void
    {
        $this->bchild = $o;
    }
}
