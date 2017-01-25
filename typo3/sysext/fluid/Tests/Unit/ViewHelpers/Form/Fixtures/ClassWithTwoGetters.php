<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Form\Fixtures;

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
 * Class ClassWithTwoGetters
 */
class ClassWithTwoGetters
{
    /**
     * @return string
     */
    public function getSomething()
    {
        return 'MyString';
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return new self();
    }
}
