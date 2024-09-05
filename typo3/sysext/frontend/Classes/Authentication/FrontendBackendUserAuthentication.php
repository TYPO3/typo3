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

namespace TYPO3\CMS\Frontend\Authentication;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * TYPO3 backend user authentication in the Frontend rendering.
 *
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class FrontendBackendUserAuthentication extends BackendUserAuthentication
{
    /**
     * Form field with login name.
     *
     * @var string
     * @internal
     */
    protected $formfield_uname = '';

    /**
     * Form field with password.
     *
     * @var string
     * @internal
     */
    protected $formfield_uident = '';

    /**
     * Formfield_status should be set to "". The value this->formfield_status is set to empty in order to
     * disable login-attempts to the backend account through this script
     *
     * @var string
     * @internal
     */
    protected $formfield_status = '';

    /**
     * Decides if the writelog() function is called at login and logout.
     *
     * @var bool
     */
    public $writeStdLog = false;

    /**
     * If the writelog() functions is called if a login-attempt has be tried without success.
     *
     * @var bool
     */
    public $writeAttemptLog = false;

    /**
     * Implementing the access checks that the TYPO3 CMS bootstrap script does before a user is ever logged in.
     * Used in the frontend.
     *
     * @return bool Returns TRUE if access is OK
     */
    public function backendCheckLogin(?ServerRequestInterface $request = null)
    {
        if (empty($this->user['uid'])) {
            return false;
        }
        // Check Hardcoded lock on BE
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'] < 0) {
            return false;
        }
        return $this->isUserAllowedToLogin();
    }

    /**
     * If a user is in a workspace, but previews the live workspace (GET keyword "LIVE") even if the user
     * has no editing permissions for this, it should still be visible, even though "be_users.workspace_perms" is set to "0".
     * If this ain't true, users without the live permission cannot see the live page, only the preview of the workspace of the user.
     */
    protected function hasEditAccessToLiveWorkspace(): bool
    {
        return true;
    }
}
