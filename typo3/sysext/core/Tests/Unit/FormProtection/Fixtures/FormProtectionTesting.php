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

namespace TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures;

use TYPO3\CMS\Core\FormProtection\AbstractFormProtection;

/**
 * Class \TYPO3\CMS\Core\Tests\Unit\FormProtection\Fixtures\FormProtectionTesting.
 *
 * This is a testing subclass of the abstract \TYPO3\CMS\Core\FormProtection\AbstractFormProtection
 * class.
 */
class FormProtectionTesting extends AbstractFormProtection
{
    /**
     * Retrieves all saved tokens.
     *
     * @return string The saved token
     */
    protected function retrieveSessionToken(): string
    {
        return $this->sessionToken = $this->generateSessionToken();
    }

    /**
     * Saves the tokens so that they can be used by a later incarnation of this
     * class.
     */
    public function persistSessionToken(): void
    {
    }
}
