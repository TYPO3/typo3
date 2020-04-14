<?php

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

namespace TYPO3\CMS\Install\FolderStructure;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * Service class to check the default folder permissions
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class DefaultPermissionsCheck
{
    /**
     * @var array Recommended values for a secure production site
     *
     * These are not the default settings (which are 0664/2775), because they might not work on every installation.
     * For security reasons these are the recommended values nevertheless (no world-readable files).
     * It's up to the admins to decide if these recommended secure values can be applied to their installation.
     */
    protected $recommended = [
        'fileCreateMask' => '0660',
        'folderCreateMask' => '2770',
    ];

    /**
     * @var array Verbose names of the settings
     */
    protected $names = [
        'fileCreateMask' => 'Default File permissions',
        'folderCreateMask' => 'Default Directory permissions',
    ];

    /**
     * Checks a BE/*mask setting for it's security
     *
     * If it permits world writing: Error
     * If it permits world reading: Warning
     * If it permits group writing: Notice
     * If it permits group reading: Notice
     * If it permits only user read/write: Ok
     *
     * @param string $which fileCreateMask or folderCreateMask
     * @return FlashMessage
     */
    public function getMaskStatus($which): FlashMessage
    {
        $octal = '0' . $GLOBALS['TYPO3_CONF_VARS']['SYS'][$which];
        $dec = octdec($octal);
        $perms = [
            'ox' => ($dec & 001) == 001,
            'ow' => ($dec & 002) == 002,
            'or' => ($dec & 004) == 004,
            'gx' => ($dec & 010) == 010,
            'gw' => ($dec & 020) == 020,
            'gr' => ($dec & 040) == 040,
            'ux' => ($dec & 0100) == 0100,
            'uw' => ($dec & 0200) == 0200,
            'ur' => ($dec & 0400) == 0400,
            'setgid' => ($dec & 02000) == 02000,
        ];
        $extraMessage = '';
        $groupPermissions = false;
        if (!$perms['uw'] || !$perms['ur']) {
            $permissionStatus = FlashMessage::ERROR;
            $extraMessage = ' (not read or writable by the user)';
        } elseif ($perms['ow']) {
            if (Environment::isWindows()) {
                $permissionStatus = FlashMessage::INFO;
                $extraMessage = ' (writable by anyone on the server). This is the default behavior on a Windows system';
            } else {
                $permissionStatus = FlashMessage::ERROR;
                $extraMessage = ' (writable by anyone on the server)';
            }
        } elseif ($perms['or']) {
            $permissionStatus = FlashMessage::NOTICE;
            $extraMessage = ' (readable by anyone on the server). This is the default set by TYPO3 CMS to be as much compatible as possible but if your system allows, please consider to change rights';
        } elseif ($perms['gw']) {
            $permissionStatus = FlashMessage::OK;
            $extraMessage = ' (group writable)';
            $groupPermissions = true;
        } elseif ($perms['gr']) {
            $permissionStatus = FlashMessage::OK;
            $extraMessage = ' (group readable)';
            $groupPermissions = true;
        } else {
            $permissionStatus = FlashMessage::OK;
        }
        $message = 'Recommended: ' . $this->recommended[$which] . '.';
        $message .= ' Currently configured as ';
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS'][$which] === $this->recommended[$which]) {
            $message .= 'recommended';
        } else {
            $message .= $GLOBALS['TYPO3_CONF_VARS']['SYS'][$which];
        }
        $message .= $extraMessage . '.';
        if ($groupPermissions) {
            $message .= ' This is fine as long as the web server\'s group only comprises trusted users.';
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup'])) {
                $message .= ' Your site is configured (SYS/createGroup) to write as group \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['createGroup'] . '\'.';
            }
        }
        return new FlashMessage(
            $message,
            $this->names[$which] . ' (SYS/' . $which . ')',
            $permissionStatus
        );
    }
}
