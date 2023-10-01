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

namespace TYPO3\CMS\Core\Resource\Exception;

use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\File;

/**
 * Exception indicating that an online media asset is already present in the target folder
 */
class OnlineMediaAlreadyExistsException extends Exception
{
    public function __construct(
        private readonly File $onlineMedia,
        int $code = 0
    ) {
        parent::__construct(
            sprintf('Online media asset "%s" does already exist in the target folder.', $onlineMedia->getName()),
            $code
        );
    }

    public function getOnlineMedia(): File
    {
        return $this->onlineMedia;
    }
}
