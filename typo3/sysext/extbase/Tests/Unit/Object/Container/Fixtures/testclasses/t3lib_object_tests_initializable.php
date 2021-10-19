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

use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;

/**
 * Class that needs initialization after instantiation
 */
class t3lib_object_tests_initializable extends AbstractDomainObject
{
    protected bool $initialized = false;

    public function initializeObject(): void
    {
        if ($this->initialized) {
            throw new \Exception('initializeObject was called a second time', 1433944932);
        }
        $this->initialized = true;
    }

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }
}
