<?php

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

namespace TYPO3\CMS\Extbase\Tests\Fixture;

use TYPO3\CMS\Extbase\Annotation as Extbase;

class ClassWithInjectProperties
{
    /**
     * @var \TYPO3\CMS\Extbase\Tests\Fixture\DummyClass
     */
    protected $dummyClass;

    /**
     * @var \TYPO3\CMS\Extbase\Tests\Fixture\SecondDummyClass
     * @Extbase\Inject
     */
    protected $secondDummyClass;
}
