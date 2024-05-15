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

namespace TYPO3\CMS\Install\Service\Exception;

use TYPO3\CMS\Core\Exception;

/**
 * Thrown when the SilentConfigurationUpgrade cannot make changes to the settings.php.
 *
 * @internal
 */
class SilentConfigurationUpgradeReadonlyException extends Exception
{
    protected $message = 'The SilentConfigurationUpgradeService needs to make changes to the settings.php but that file is read-only. ' .
        'Please (temporarily) clear the read-only status and open the install tool or run the UpgradeWizards command on CLI (typo3 upgrade:run). ' .
        'Once the SilentConfigurationUpgrade has been run, you may restrict writing to the settings.php again.';

    public function __construct(int $code = 0, ?\Throwable $throwable = null)
    {
        parent::__construct($this->message, $code, $throwable);
    }
}
