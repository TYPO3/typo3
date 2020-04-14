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

namespace TYPO3\CMS\Backend\Controller\File;

/**
 * Class ReplaceFileController
 *
 * @deprecated see \TYPO3\CMS\Filelist\Controller\File\ReplaceFileController
 */
class ReplaceFileController extends \TYPO3\CMS\Filelist\Controller\File\ReplaceFileController
{
    public function __construct()
    {
        trigger_error(
            'Using \TYPO3\CMS\Backend\Controller\File\ReplaceFileController is deprecated and internal, please use an own controller.',
            E_USER_DEPRECATED
        );
    }
}
