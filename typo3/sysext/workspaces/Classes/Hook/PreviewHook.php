<?php
namespace TYPO3\CMS\Workspaces\Hook;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook for checking if the preview mode is activated
 * preview mode = show a page of a workspace without having to log in
 */
class PreviewHook
{
    /**
     * Set preview keyword, eg:
     * $previewUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL').'index.php?ADMCMD_prev='.$this->compilePreviewKeyword($GLOBALS['BE_USER']->user['uid'], 120);
     *
     * @todo for sys_preview:
     * - Add a comment which can be shown to previewer in frontend in some way (plus maybe ability to write back, take other action?)
     * - Add possibility for the preview keyword to work in the backend as well: So it becomes a quick way to a certain action of sorts?
     *
     * @param string $backendUserUid 32 byte MD5 hash keyword for the URL: "?ADMCMD_prev=[keyword]
     * @param int $ttl Time-To-Live for keyword
     * @param int|null $fullWorkspace Which workspace ID to preview.
     * @return string Returns keyword to use in URL for ADMCMD_prev=
     */
    public function compilePreviewKeyword($backendUserUid, $ttl = 172800, $fullWorkspace = null)
    {
        $fieldData = [
            'keyword' => md5(uniqid(microtime(), true)),
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'endtime' => $GLOBALS['EXEC_TIME'] + $ttl,
            'config' => json_encode([
                'fullWorkspace' => $fullWorkspace,
                'BEUSER_uid' => $backendUserUid
            ])
        ];
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_preview')
            ->insert(
                'sys_preview',
                $fieldData
            );

        return $fieldData['keyword'];
    }

    /**
     * easy function to just return the number of hours
     * a preview link is valid, based on the TSconfig value "options.workspaces.previewLinkTTLHours"
     * by default, it's 48hs
     *
     * @return int The hours as a number
     */
    public function getPreviewLinkLifetime()
    {
        $ttlHours = (int)$GLOBALS['BE_USER']->getTSConfigVal('options.workspaces.previewLinkTTLHours');
        return $ttlHours ? $ttlHours : 24 * 2;
    }
}
