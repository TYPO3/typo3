<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

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
 * A caching backend which forgets everything immediately
 * Used in FactoryTest
 *
 * This file is a backport from FLOW3
 */
class MockBackend extends \TYPO3\CMS\Core\Cache\Backend\NullBackend
{
    /**
     * @var mixed
     */
    protected $someOption;

    /**
     * Sets some option
     *
     * @param mixed $value
     * @return void
     */
    public function setSomeOption($value)
    {
        $this->someOption = $value;
    }

    /**
     * Returns the option value
     *
     * @return mixed
     */
    public function getSomeOption()
    {
        return $this->someOption;
    }
}
