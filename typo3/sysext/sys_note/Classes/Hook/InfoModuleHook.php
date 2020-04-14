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

namespace TYPO3\CMS\SysNote\Hook;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\SysNote\Controller\NoteController;

/**
 * Hook for the info module
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class InfoModuleHook
{
    /**
     * Add sys_notes as additional content to the footer of the info module
     *
     * @return string
     */
    public function render()
    {
        $controller = GeneralUtility::makeInstance(NoteController::class);
        $id = (int)GeneralUtility::_GP('id');
        return $controller->listAction($id);
    }
}
