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

namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

use TYPO3\CMS\Core\Cache\Backend\NullBackend;

/**
 * A caching backend which forgets everything immediately
 * Used in FactoryTest
 */
class MockBackend extends NullBackend
{
    /**
     * @var mixed
     */
    protected $someOption;

    /**
     * Sets some option
     *
     * @param mixed $value
     */
    public function setSomeOption($value): void
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
