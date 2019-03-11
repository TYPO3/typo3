<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\Controller\File;

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
 * Class CreateFolderController
 *
 * @deprecated see \TYPO3\CMS\Filelist\Controller\File\CreateFolderController
 */
class CreateFolderController extends \TYPO3\CMS\Filelist\Controller\File\CreateFolderController
{
    public function __construct()
    {
        trigger_error(
            'Using \TYPO3\CMS\Backend\Controller\File\CreateFolderController is deprecated and internal, please use an own controller.',
            E_USER_DEPRECATED
        );
    }
}
