<?php
namespace TYPO3\CMS\Felogin\Controller;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Plugin 'Website User Login' for the 'felogin' extension.
 */
class FrontendLoginController extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    /**
     * Same as class name
     *
     * @var string
     */
    public $prefixId = 'tx_felogin_pi1';

    /**
     * The extension key.
     *
     * @var string
     */
    public $extKey = 'felogin';

    /**
     * @var bool
     */
    public $pi_checkCHash = false;

    /**
     * @var bool
     */
    public $pi_USER_INT_obj = true;

    /**
     * Is user logged in?
     *
     * @var bool
     */
    protected $userIsLoggedIn;

    /**
     * Holds the template for FE rendering
     *
     * @var string
     */
    protected $template;

    /**
     * Upload directory, used for flexform template files
     *
     * @var string
     */
    protected $uploadDir;

    /**
     * URL for the redirect
     *
     * @var string
     */
    protected $redirectUrl;

    /**
     * Flag for disable the redirect
     *
     * @var bool
     */
    protected $noRedirect = false;

    /**
     * Logintype (given as GPvar), possible: login, logout
     *
     * @var string
     */
    protected $logintype;

    /**
     * A list of page UIDs, either an integer or a comma-separated list of integers
     *
     * @var string
     */
    public $spid;

    /**
     * Referrer
     *
     * @var string
     */
    public $referer;

    /**
     * The main method of the plugin
     *
     * @param string $content The PlugIn content
     * @param array $conf The PlugIn configuration
     * @return string The content that is displayed on the website
     */
    public function main($content, $conf)
    {
        // Loading TypoScript array into object variable:
        $this->conf = $conf;
        $this->uploadDir = 'uploads/tx_felogin/';
        // Loading default pivars
        $this->pi_setPiVarDefaults();
        // Loading language-labels
        $this->pi_loadLL('EXT:felogin/Resources/Private/Language/locallang.xlf');
        // Init FlexForm configuration for plugin:
        $this->pi_initPIflexForm();
        $this->mergeflexFormValuesIntoConf();
        // Get storage PIDs:
        if ($this->conf['storagePid']) {
            if ((int)$this->conf['recursive']) {
                $this->spid = $this->pi_getPidList($this->conf['storagePid'], (int)$this->conf['recursive']);
            } else {
                $this->spid = $this->conf['storagePid'];
            }
        } else {
            GeneralUtility::deprecationLog('Extension "felogin" must have a storagePid set via TypoScript or the plugin configuration.');
            $pids = $this->frontendController->getStorageSiterootPids();
            $this->spid = $pids['_STORAGE_PID'];
        }
        // GPvars:
        $this->logintype = GeneralUtility::_GP('logintype');
        $this->referer = $this->validateRedirectUrl(GeneralUtility::_GP('referer'));
        $this->noRedirect = $this->piVars['noredirect'] || $this->conf['redirectDisable'];
        // If config.typolinkLinkAccessRestrictedPages is set, the var is return_url
        $returnUrl = GeneralUtility::_GP('return_url');
        if ($returnUrl) {
            $this->redirectUrl = $returnUrl;
        } else {
            $this->redirectUrl = GeneralUtility::_GP('redirect_url');
        }
        $this->redirectUrl = $this->validateRedirectUrl($this->redirectUrl);
        // Get Template
        $templateFile = $this->conf['templateFile'] ?: 'EXT:felogin/Resources/Private/Templates/FrontendLogin.html';
        $this->template = $this->cObj->fileResource($templateFile);
        // Is user logged in?
        $this->userIsLoggedIn = $this->frontendController->loginUser;
        // Redirect
        if ($this->conf['redirectMode'] && !$this->conf['redirectDisable'] && !$this->noRedirect) {
            $redirectUrl = $this->processRedirect();
            if (!empty($redirectUrl)) {
                $this->redirectUrl = $this->conf['redirectFirstMethod'] ? array_shift($redirectUrl) : array_pop($redirectUrl);
            } else {
                $this->redirectUrl = '';
            }
        }
        // What to display
        $content = '';
        if ($this->piVars['forgot'] && $this->conf['showForgotPasswordLink']) {
            $content .= $this->showForgot();
        } elseif ($this->piVars['forgothash']) {
            $content .= $this->changePassword();
        } else {
            if ($this->userIsLoggedIn && !$this->logintype) {
                $content .= $this->showLogout();
            } else {
                $content .= $this->showLogin();
            }
        }
        // Process the redirect
        if (($this->logintype === 'login' || $this->logintype === 'logout') && $this->redirectUrl && !$this->noRedirect) {
            if (!$this->frontendController->fe_user->isCookieSet() && $this->userIsLoggedIn) {
                $content .= $this->cObj->stdWrap($this->pi_getLL('cookie_warning'), $this->conf['cookieWarning_stdWrap.']);
            } else {
                // Add hook for extra processing before redirect
                if (
                    isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['beforeRedirect']) &&
                    is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['beforeRedirect'])
                ) {
                    $_params = [
                        'loginType' => $this->logintype,
                        'redirectUrl' => &$this->redirectUrl
                    ];
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['beforeRedirect'] as $_funcRef) {
                        if ($_funcRef) {
                            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                        }
                    }
                }
                \TYPO3\CMS\Core\Utility\HttpUtility::redirect($this->redirectUrl);
            }
        }
        // Adds hook for processing of extra item markers / special
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['postProcContent'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['postProcContent'])
        ) {
            $_params = [
                'content' => $content
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['postProcContent'] as $_funcRef) {
                $content = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        return $this->conf['wrapContentInBaseClass'] ? $this->pi_wrapInBaseClass($content) : $content;
    }

    /**
     * Shows the forgot password form
     *
     * @return string Content
     */
    protected function showForgot()
    {
        $subpart = $this->cObj->getSubpart($this->template, '###TEMPLATE_FORGOT###');
        $subpartArray = ($linkpartArray = []);
        $postData = GeneralUtility::_POST($this->prefixId);
        if ($postData['forgot_email']) {
            // Get hashes for compare
            $postedHash = $postData['forgot_hash'];
            $hashData = $this->frontendController->fe_user->getKey('ses', 'forgot_hash');
            if ($postedHash === $hashData['forgot_hash']) {
                $row = false;
                // Look for user record
                $data = $this->databaseConnection->fullQuoteStr($this->piVars['forgot_email'], 'fe_users');
                $res = $this->databaseConnection->exec_SELECTquery(
                    'uid, username, password, email',
                    'fe_users',
                    '(email=' . $data . ' OR username=' . $data . ') AND pid IN (' . $this->databaseConnection->cleanIntList($this->spid) . ') ' . $this->cObj->enableFields('fe_users')
                );
                if ($this->databaseConnection->sql_num_rows($res)) {
                    $row = $this->databaseConnection->sql_fetch_assoc($res);
                }
                $error = null;
                if ($row) {
                    // Generate an email with the hashed link
                    $error = $this->generateAndSendHash($row);
                } elseif ($this->conf['exposeNonexistentUserInForgotPasswordDialog']) {
                    $error = $this->pi_getLL('ll_forgot_reset_message_error');
                }
                // Generate message
                if ($error) {
                    $markerArray['###STATUS_MESSAGE###'] = $this->cObj->stdWrap($error, $this->conf['forgotErrorMessage_stdWrap.']);
                } else {
                    $markerArray['###STATUS_MESSAGE###'] = $this->cObj->stdWrap(
                        $this->pi_getLL('ll_forgot_reset_message_emailSent'),
                        $this->conf['forgotResetMessageEmailSentMessage_stdWrap.']
                    );
                }
                $subpartArray['###FORGOT_FORM###'] = '';
            } else {
                // Wrong email
                $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('forgot_reset_message', $this->conf['forgotMessage_stdWrap.']);
                $markerArray['###BACKLINK_LOGIN###'] = '';
            }
        } else {
            $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('forgot_reset_message', $this->conf['forgotMessage_stdWrap.']);
            $markerArray['###BACKLINK_LOGIN###'] = '';
        }
        $markerArray['###BACKLINK_LOGIN###'] = $this->getPageLink($this->pi_getLL('ll_forgot_header_backToLogin', '', true), []);
        $markerArray['###STATUS_HEADER###'] = $this->getDisplayText('forgot_header', $this->conf['forgotHeader_stdWrap.']);
        $markerArray['###LEGEND###'] = $this->pi_getLL('legend', $this->pi_getLL('reset_password'), true);
        $markerArray['###ACTION_URI###'] = $this->getPageLink('', [$this->prefixId . '[forgot]' => 1], true);
        $markerArray['###EMAIL_LABEL###'] = $this->pi_getLL('your_email', '', true);
        $markerArray['###FORGOT_PASSWORD_ENTEREMAIL###'] = $this->pi_getLL('forgot_password_enterEmail', '', true);
        $markerArray['###FORGOT_EMAIL###'] = $this->prefixId . '[forgot_email]';
        $markerArray['###SEND_PASSWORD###'] = $this->pi_getLL('reset_password', '', true);
        $markerArray['###DATA_LABEL###'] = $this->pi_getLL('ll_enter_your_data', '', true);
        $markerArray = array_merge($markerArray, $this->getUserFieldMarkers());
        // Generate hash
        $hash = md5($this->generatePassword(3));
        $markerArray['###FORGOTHASH###'] = $hash;
        // Set hash in feuser session
        $this->frontendController->fe_user->setKey('ses', 'forgot_hash', ['forgot_hash' => $hash]);
        return $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, $linkpartArray);
    }

    /**
     * This function checks the hash from link and checks the validity. If it's valid it shows the form for
     * changing the password and process the change of password after submit, if not valid it returns the error message
     *
     * @return string The content.
     */
    protected function changePassword()
    {
        $subpartArray = ($linkpartArray = []);
        $done = false;
        $minLength = (int)$this->conf['newPasswordMinLength'] ?: 6;
        $subpart = $this->cObj->getSubpart($this->template, '###TEMPLATE_CHANGEPASSWORD###');
        $markerArray['###STATUS_HEADER###'] = $this->getDisplayText('change_password_header', $this->conf['changePasswordHeader_stdWrap.']);
        $markerArray['###STATUS_MESSAGE###'] = sprintf($this->getDisplayText(
            'change_password_message',
            $this->conf['changePasswordMessage_stdWrap.']
        ), $minLength);

        $markerArray['###BACKLINK_LOGIN###'] = '';
        $uid = $this->piVars['user'];
        $piHash = $this->piVars['forgothash'];
        $hash = explode('|', rawurldecode($piHash));
        if ((int)$uid === 0) {
            $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText(
                'change_password_notvalid_message',
                $this->conf['changePasswordNotValidMessage_stdWrap.']
            );
            $subpartArray['###CHANGEPASSWORD_FORM###'] = '';
        } else {
            $user = $this->pi_getRecord('fe_users', (int)$uid);
            $userHash = $user['felogin_forgotHash'];
            $compareHash = explode('|', $userHash);
            if (!$compareHash || !$compareHash[1] || $compareHash[0] < time() || $hash[0] != $compareHash[0] || md5($hash[1]) != $compareHash[1]) {
                $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText(
                    'change_password_notvalid_message',
                    $this->conf['changePasswordNotValidMessage_stdWrap.']
                );
                $subpartArray['###CHANGEPASSWORD_FORM###'] = '';
            } else {
                // All is fine, continue with new password
                $postData = GeneralUtility::_POST($this->prefixId);
                if (isset($postData['changepasswordsubmit'])) {
                    if (strlen($postData['password1']) < $minLength) {
                        $markerArray['###STATUS_MESSAGE###'] = sprintf($this->getDisplayText(
                            'change_password_tooshort_message',
                            $this->conf['changePasswordTooShortMessage_stdWrap.']),
                            $minLength
                        );
                    } elseif ($postData['password1'] != $postData['password2']) {
                        $markerArray['###STATUS_MESSAGE###'] = sprintf($this->getDisplayText(
                            'change_password_notequal_message',
                            $this->conf['changePasswordNotEqualMessage_stdWrap.']),
                            $minLength
                        );
                    } else {
                        $newPass = $postData['password1'];
                        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['password_changed']) {
                            $_params = [
                                'user' => $user,
                                'newPassword' => $newPass,
                                'newPasswordUnencrypted' => $newPass
                            ];
                            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['password_changed'] as $_funcRef) {
                                if ($_funcRef) {
                                    GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                                }
                            }
                            $newPass = $_params['newPassword'];
                        }
                        // Save new password and clear DB-hash
                        $res = $this->databaseConnection->exec_UPDATEquery(
                            'fe_users',
                            'uid=' . $user['uid'],
                            ['password' => $newPass, 'felogin_forgotHash' => '', 'tstamp' => $GLOBALS['EXEC_TIME']]
                        );
                        $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText(
                            'change_password_done_message',
                            $this->conf['changePasswordDoneMessage_stdWrap.']
                        );
                        $done = true;
                        $subpartArray['###CHANGEPASSWORD_FORM###'] = '';
                        $markerArray['###BACKLINK_LOGIN###'] = $this->getPageLink(
                            $this->pi_getLL('ll_forgot_header_backToLogin', '', true),
                            [$this->prefixId . '[redirectReferrer]' => 'off']
                        );
                    }
                }
                if (!$done) {
                    // Change password form
                    $markerArray['###ACTION_URI###'] = $this->getPageLink('', [
                        $this->prefixId . '[user]' => $user['uid'],
                        $this->prefixId . '[forgothash]' => $piHash
                    ], true);
                    $markerArray['###LEGEND###'] = $this->pi_getLL('change_password', '', true);
                    $markerArray['###NEWPASSWORD1_LABEL###'] = $this->pi_getLL('newpassword_label1', '', true);
                    $markerArray['###NEWPASSWORD2_LABEL###'] = $this->pi_getLL('newpassword_label2', '', true);
                    $markerArray['###NEWPASSWORD1###'] = $this->prefixId . '[password1]';
                    $markerArray['###NEWPASSWORD2###'] = $this->prefixId . '[password2]';
                    $markerArray['###STORAGE_PID###'] = $this->spid;
                    $markerArray['###SEND_PASSWORD###'] = $this->pi_getLL('change_password', '', true);
                    $markerArray['###FORGOTHASH###'] = $piHash;
                }
            }
        }
        return $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, $linkpartArray);
    }

    /**
     * Generates a hashed link and send it with email
     *
     * @param array $user Contains user data
     * @return string Empty string with success, error message with no success
     */
    protected function generateAndSendHash($user)
    {
        $hours = (int)$this->conf['forgotLinkHashValidTime'] > 0 ? (int)$this->conf['forgotLinkHashValidTime'] : 24;
        $validEnd = time() + 3600 * $hours;
        $validEndString = date($this->conf['dateFormat'], $validEnd);
        $hash = md5(GeneralUtility::generateRandomBytes(64));
        $randHash = $validEnd . '|' . $hash;
        $randHashDB = $validEnd . '|' . md5($hash);
        // Write hash to DB
        $res = $this->databaseConnection->exec_UPDATEquery('fe_users', 'uid=' . $user['uid'], ['felogin_forgotHash' => $randHashDB]);
        // Send hashlink to user
        $this->conf['linkPrefix'] = -1;
        $isAbsRefPrefix = !empty($this->frontendController->absRefPrefix);
        $isBaseURL = !empty($this->frontendController->baseUrl);
        $isFeloginBaseURL = !empty($this->conf['feloginBaseURL']);
        $link = $this->pi_getPageLink($this->frontendController->id, '', [
            rawurlencode($this->prefixId . '[user]') => $user['uid'],
            rawurlencode($this->prefixId . '[forgothash]') => $randHash
        ]);
        // Prefix link if necessary
        if ($isFeloginBaseURL) {
            // First priority, use specific base URL
            // "absRefPrefix" must be removed first, otherwise URL will be prepended twice
            if ($isAbsRefPrefix) {
                $link = substr($link, strlen($this->frontendController->absRefPrefix));
            }
            $link = $this->conf['feloginBaseURL'] . $link;
        } elseif ($isAbsRefPrefix) {
            // Second priority
            // absRefPrefix must not necessarily contain a hostname and URL scheme, so add it if needed
            $link = GeneralUtility::locationHeaderUrl($link);
        } elseif ($isBaseURL) {
            // Third priority
            // Add the global base URL to the link
            $link = $this->frontendController->baseUrlWrap($link);
        } else {
            // No prefix is set, return the error
            return $this->pi_getLL('ll_change_password_nolinkprefix_message');
        }
        $msg = sprintf($this->pi_getLL('ll_forgot_validate_reset_password'), $user['username'], $link, $validEndString);
        // Add hook for extra processing of mail message
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['forgotPasswordMail'])
            && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['forgotPasswordMail'])
        ) {
            $params = [
                'message' => &$msg,
                'user' => &$user
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['forgotPasswordMail'] as $reference) {
                if ($reference) {
                    GeneralUtility::callUserFunction($reference, $params, $this);
                }
            }
        }
        if ($user['email']) {
            $this->cObj->sendNotifyEmail($msg, $user['email'], '', $this->conf['email_from'], $this->conf['email_fromName'], $this->conf['replyTo']);
        }

        return '';
    }

    /**
     * Shows logout form
     *
     * @return string The content.
     */
    protected function showLogout()
    {
        $subpart = $this->cObj->getSubpart($this->template, '###TEMPLATE_LOGOUT###');
        $subpartArray = ($linkpartArray = []);
        $markerArray['###STATUS_HEADER###'] = $this->getDisplayText('status_header', $this->conf['logoutHeader_stdWrap.']);
        $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('status_message', $this->conf['logoutMessage_stdWrap.']);
        $this->cObj->stdWrap($this->flexFormValue('message', 's_status'), $this->conf['logoutMessage_stdWrap.']);
        $markerArray['###LEGEND###'] = $this->pi_getLL('logout', '', true);
        $markerArray['###ACTION_URI###'] = $this->getPageLink('', [], true);
        $markerArray['###LOGOUT_LABEL###'] = $this->pi_getLL('logout', '', true);
        $markerArray['###NAME###'] = htmlspecialchars($this->frontendController->fe_user->user['name']);
        $markerArray['###STORAGE_PID###'] = $this->spid;
        $markerArray['###USERNAME###'] = htmlspecialchars($this->frontendController->fe_user->user['username']);
        $markerArray['###USERNAME_LABEL###'] = $this->pi_getLL('username', '', true);
        $markerArray['###NOREDIRECT###'] = $this->noRedirect ? '1' : '0';
        $markerArray['###PREFIXID###'] = $this->prefixId;
        $markerArray = array_merge($markerArray, $this->getUserFieldMarkers());
        if ($this->redirectUrl) {
            // Use redirectUrl for action tag because of possible access restricted pages
            $markerArray['###ACTION_URI###'] = htmlspecialchars($this->redirectUrl);
            $this->redirectUrl = '';
        }
        return $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, $linkpartArray);
    }

    /**
     * Shows login form
     *
     * @return string Content
     */
    protected function showLogin()
    {
        $subpart = $this->cObj->getSubpart($this->template, '###TEMPLATE_LOGIN###');
        $subpartArray = ($linkpartArray = ($markerArray = []));
        $gpRedirectUrl = '';
        $markerArray['###LEGEND###'] = $this->pi_getLL('oLabel_header_welcome', '', true);
        if ($this->logintype === 'login') {
            if ($this->userIsLoggedIn) {
                // login success
                $markerArray['###STATUS_HEADER###'] = $this->getDisplayText('success_header', $this->conf['successHeader_stdWrap.']);
                $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('success_message', $this->conf['successMessage_stdWrap.']);
                $markerArray = array_merge($markerArray, $this->getUserFieldMarkers());
                $subpartArray['###LOGIN_FORM###'] = '';
                // Hook for general actions after after login has been confirmed (by Thomas Danzl <thomas@danzl.org>)
                if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed']) {
                    $_params = [];
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_confirmed'] as $_funcRef) {
                        if ($_funcRef) {
                            GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                        }
                    }
                }
                // show logout form directly
                if ($this->conf['showLogoutFormAfterLogin']) {
                    $this->redirectUrl = '';
                    return $this->showLogout();
                }
            } else {
                // Hook for general actions on login error
                if (
                    isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_error'])
                    && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_error'])
                ) {
                    $params = [];
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['login_error'] as $funcRef) {
                        if ($funcRef) {
                            GeneralUtility::callUserFunction($funcRef, $params, $this);
                        }
                    }
                }
                // login error
                $markerArray['###STATUS_HEADER###'] = $this->getDisplayText('error_header', $this->conf['errorHeader_stdWrap.']);
                $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('error_message', $this->conf['errorMessage_stdWrap.']);
                $gpRedirectUrl = GeneralUtility::_GP('redirect_url');
            }
        } else {
            if ($this->logintype === 'logout') {
                // login form after logout
                $markerArray['###STATUS_HEADER###'] = $this->getDisplayText('logout_header', $this->conf['logoutHeader_stdWrap.']);
                $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('logout_message', $this->conf['logoutMessage_stdWrap.']);
            } else {
                // login form
                $markerArray['###STATUS_HEADER###'] = $this->getDisplayText('welcome_header', $this->conf['welcomeHeader_stdWrap.']);
                $markerArray['###STATUS_MESSAGE###'] = $this->getDisplayText('welcome_message', $this->conf['welcomeMessage_stdWrap.']);
            }
        }

        // This hook allows to call User JS functions.
        // The methods should also set the required JS functions to get included
        $onSubmit = '';
        $extraHidden = '';
        $onSubmitAr = [];
        $extraHiddenAr = [];
        // Check for referer redirect method. if present, save referer in form field
        if (GeneralUtility::inList($this->conf['redirectMode'], 'referer') || GeneralUtility::inList($this->conf['redirectMode'], 'refererDomains')) {
            $referer = $this->referer ? $this->referer : GeneralUtility::getIndpEnv('HTTP_REFERER');
            if ($referer) {
                $extraHiddenAr[] = '<input type="hidden" name="referer" value="' . htmlspecialchars($referer) . '" />';
                if ($this->piVars['redirectReferrer'] === 'off') {
                    $extraHiddenAr[] = '<input type="hidden" name="' . $this->prefixId . '[redirectReferrer]" value="off" />';
                }
            }
        }
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'])) {
            $_params = [];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['loginFormOnSubmitFuncs'] as $funcRef) {
                list($onSub, $hid) = GeneralUtility::callUserFunction($funcRef, $_params, $this);
                $onSubmitAr[] = $onSub;
                $extraHiddenAr[] = $hid;
            }
        }
        if (!empty($onSubmitAr)) {
            $onSubmit = implode('; ', $onSubmitAr) . '; return true;';
        }
        if (!empty($extraHiddenAr)) {
            $extraHidden = implode(LF, $extraHiddenAr);
        }
        if (!$gpRedirectUrl && $this->redirectUrl) {
            $gpRedirectUrl = $this->redirectUrl;
        }
        // Login form
        $markerArray['###ACTION_URI###'] = $this->getPageLink('', [], true);
        // Used by kb_md5fepw extension...
        $markerArray['###EXTRA_HIDDEN###'] = $extraHidden;
        $markerArray['###LEGEND###'] = $this->pi_getLL('login', '', true);
        $markerArray['###LOGIN_LABEL###'] = $this->pi_getLL('login', '', true);
        // Used by kb_md5fepw extension...
        $markerArray['###ON_SUBMIT###'] = $onSubmit;
        $markerArray['###PASSWORD_LABEL###'] = $this->pi_getLL('password', '', true);
        $markerArray['###STORAGE_PID###'] = $this->spid;
        $markerArray['###USERNAME_LABEL###'] = $this->pi_getLL('username', '', true);
        $markerArray['###REDIRECT_URL###'] = htmlspecialchars($gpRedirectUrl);
        $markerArray['###NOREDIRECT###'] = $this->noRedirect ? '1' : '0';
        $markerArray['###PREFIXID###'] = $this->prefixId;
        $markerArray = array_merge($markerArray, $this->getUserFieldMarkers());
        if ($this->conf['showForgotPasswordLink']) {
            $linkpartArray['###FORGOT_PASSWORD_LINK###'] = explode('|', $this->getPageLink('|', [$this->prefixId . '[forgot]' => 1]));
            $markerArray['###FORGOT_PASSWORD###'] = $this->pi_getLL('ll_forgot_header', '', true);
        } else {
            $subpartArray['###FORGOTP_VALID###'] = '';
        }
        // The permanent login checkbox should only be shown if permalogin is not deactivated (-1),
        // not forced to be always active (2) and lifetime is greater than 0
        $permalogin = (int)$GLOBALS['TYPO3_CONF_VARS']['FE']['permalogin'];
        if (
            $this->conf['showPermaLogin']
            && ($permalogin === 0 || $permalogin === 1)
            && $GLOBALS['TYPO3_CONF_VARS']['FE']['lifetime'] > 0
        ) {
            $markerArray['###PERMALOGIN###'] = $this->pi_getLL('permalogin', '', true);
            if ($permalogin === 1) {
                $markerArray['###PERMALOGIN_HIDDENFIELD_ATTRIBUTES###'] = 'disabled="disabled"';
                $markerArray['###PERMALOGIN_CHECKBOX_ATTRIBUTES###'] = 'checked="checked"';
            } else {
                $markerArray['###PERMALOGIN_HIDDENFIELD_ATTRIBUTES###'] = '';
                $markerArray['###PERMALOGIN_CHECKBOX_ATTRIBUTES###'] = '';
            }
        } else {
            $subpartArray['###PERMALOGIN_VALID###'] = '';
        }
        return $this->cObj->substituteMarkerArrayCached($subpart, $markerArray, $subpartArray, $linkpartArray);
    }

    /**
     * Process redirect methods. The function searches for a redirect url using all configured methods.
     *
     * @return array Redirect URLs
     */
    protected function processRedirect()
    {
        $redirect_url = [];
        if ($this->conf['redirectMode']) {
            $redirectMethods = GeneralUtility::trimExplode(',', $this->conf['redirectMode'], true);
            foreach ($redirectMethods as $redirMethod) {
                if ($this->frontendController->loginUser && $this->logintype === 'login') {
                    // Logintype is needed because the login-page wouldn't be accessible anymore after a login (would always redirect)
                    switch ($redirMethod) {
                        case 'groupLogin':
                            // taken from dkd_redirect_at_login written by Ingmar Schlecht; database-field changed
                            $groupData = $this->frontendController->fe_user->groupData;
                            if (!empty($groupData['uid'])) {
                                // take the first group with a redirect page
                                $row = $this->databaseConnection->exec_SELECTgetSingleRow(
                                    'felogin_redirectPid',
                                    $this->frontendController->fe_user->usergroup_table,
                                    'felogin_redirectPid<>\'\' AND uid IN (' . implode(',', $groupData['uid']) . ')'
                                );
                                if ($row) {
                                    $redirect_url[] = $this->pi_getPageLink($row['felogin_redirectPid']);
                                }
                            }
                            break;
                        case 'userLogin':
                            $row = $this->databaseConnection->exec_SELECTgetSingleRow(
                                'felogin_redirectPid',
                                $this->frontendController->fe_user->user_table,
                                $this->frontendController->fe_user->userid_column . '=' . $this->frontendController->fe_user->user['uid'] . ' AND felogin_redirectPid<>\'\''
                            );
                            if ($row) {
                                $redirect_url[] = $this->pi_getPageLink($row['felogin_redirectPid']);
                            }
                            break;
                        case 'login':
                            if ($this->conf['redirectPageLogin']) {
                                $redirect_url[] = $this->pi_getPageLink((int)$this->conf['redirectPageLogin']);
                            }
                            break;
                        case 'getpost':
                            $redirect_url[] = $this->redirectUrl;
                            break;
                        case 'referer':
                            // Avoid redirect when logging in after changing password
                            if ($this->piVars['redirectReferrer'] !== 'off') {
                                // Avoid forced logout, when trying to login immediately after a logout
                                $redirect_url[] = preg_replace('/[&?]logintype=[a-z]+/', '', $this->referer);
                            }
                            break;
                        case 'refererDomains':
                            // Auto redirect.
                            // Feature to redirect to the page where the user came from (HTTP_REFERER).
                            // Allowed domains to redirect to, can be configured with plugin.tx_felogin_pi1.domains
                            // Thanks to plan2.net / Martin Kutschker for implementing this feature.
                            // also avoid redirect when logging in after changing password
                            if ($this->conf['domains'] && $this->piVars['redirectReferrer'] !== 'off') {
                                $url = $this->referer;
                                // Is referring url allowed to redirect?
                                $match = [];
                                if (preg_match('#^http://([[:alnum:]._-]+)/#', $url, $match)) {
                                    $redirect_domain = $match[1];
                                    $found = false;
                                    foreach (GeneralUtility::trimExplode(',', $this->conf['domains'], true) as $d) {
                                        if (preg_match('/(?:^|\\.)' . $d . '$/', $redirect_domain)) {
                                            $found = true;
                                            break;
                                        }
                                    }
                                    if (!$found) {
                                        $url = '';
                                    }
                                }
                                // Avoid forced logout, when trying to login immediately after a logout
                                if ($url) {
                                    $redirect_url[] = preg_replace('/[&?]logintype=[a-z]+/', '', $url);
                                }
                            }
                            break;
                    }
                } elseif ($this->logintype === 'login') {
                    // after login-error
                    switch ($redirMethod) {
                        case 'loginError':
                            if ($this->conf['redirectPageLoginError']) {
                                $redirect_url[] = $this->pi_getPageLink((int)$this->conf['redirectPageLoginError']);
                            }
                            break;
                    }
                } elseif ($this->logintype == '' && $redirMethod == 'login' && $this->conf['redirectPageLogin']) {
                    // If login and page not accessible
                    $this->cObj->typoLink('', [
                        'parameter' => $this->conf['redirectPageLogin'],
                        'linkAccessRestrictedPages' => true
                    ]);
                    $redirect_url[] = $this->cObj->lastTypoLinkUrl;
                } elseif ($this->logintype == '' && $redirMethod == 'logout' && $this->conf['redirectPageLogout'] && $this->frontendController->loginUser) {
                    // If logout and page not accessible
                    $redirect_url[] = $this->pi_getPageLink((int)$this->conf['redirectPageLogout']);
                } elseif ($this->logintype === 'logout') {
                    // after logout
                    // Hook for general actions after after logout has been confirmed
                    if ($this->logintype === 'logout' && $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['logout_confirmed']) {
                        $_params = [];
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['logout_confirmed'] as $_funcRef) {
                            if ($_funcRef) {
                                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                            }
                        }
                    }
                    switch ($redirMethod) {
                        case 'logout':
                            if ($this->conf['redirectPageLogout']) {
                                $redirect_url[] = $this->pi_getPageLink((int)$this->conf['redirectPageLogout']);
                            }
                            break;
                    }
                } else {
                    // not logged in
                    // Placeholder for maybe future options
                    switch ($redirMethod) {
                        case 'getpost':
                            // Preserve the get/post value
                            $redirect_url[] = $this->redirectUrl;
                            break;
                    }
                }
            }
        }
        // Remove empty values, but keep "0" as value (that's why "strlen" is used as second parameter)
        if (!empty($redirect_url)) {
            return array_filter($redirect_url, 'strlen');
        }
        return [];
    }

    /**
     * Reads flexform configuration and merge it with $this->conf
     *
     * @return void
     */
    protected function mergeflexFormValuesIntoConf()
    {
        $flex = [];
        if ($this->flexFormValue('showForgotPassword', 'sDEF')) {
            $flex['showForgotPasswordLink'] = $this->flexFormValue('showForgotPassword', 'sDEF');
        }
        if ($this->flexFormValue('showPermaLogin', 'sDEF')) {
            $flex['showPermaLogin'] = $this->flexFormValue('showPermaLogin', 'sDEF');
        }
        if ($this->flexFormValue('showLogoutFormAfterLogin', 'sDEF')) {
            $flex['showLogoutFormAfterLogin'] = $this->flexFormValue('showLogoutFormAfterLogin', 'sDEF');
        }
        if ($this->flexFormValue('pages', 'sDEF')) {
            $flex['pages'] = $this->flexFormValue('pages', 'sDEF');
        }
        if ($this->flexFormValue('recursive', 'sDEF')) {
            $flex['recursive'] = $this->flexFormValue('recursive', 'sDEF');
        }
        if ($this->flexFormValue('templateFile', 'sDEF')) {
            $flex['templateFile'] = $this->uploadDir . $this->flexFormValue('templateFile', 'sDEF');
        }
        if ($this->flexFormValue('redirectMode', 's_redirect')) {
            $flex['redirectMode'] = $this->flexFormValue('redirectMode', 's_redirect');
        }
        if ($this->flexFormValue('redirectFirstMethod', 's_redirect')) {
            $flex['redirectFirstMethod'] = $this->flexFormValue('redirectFirstMethod', 's_redirect');
        }
        if ($this->flexFormValue('redirectDisable', 's_redirect')) {
            $flex['redirectDisable'] = $this->flexFormValue('redirectDisable', 's_redirect');
        }
        if ($this->flexFormValue('redirectPageLogin', 's_redirect')) {
            $flex['redirectPageLogin'] = $this->flexFormValue('redirectPageLogin', 's_redirect');
        }
        if ($this->flexFormValue('redirectPageLoginError', 's_redirect')) {
            $flex['redirectPageLoginError'] = $this->flexFormValue('redirectPageLoginError', 's_redirect');
        }
        if ($this->flexFormValue('redirectPageLogout', 's_redirect')) {
            $flex['redirectPageLogout'] = $this->flexFormValue('redirectPageLogout', 's_redirect');
        }
        $pid = $flex['pages'] ? $this->pi_getPidList($flex['pages'], $flex['recursive']) : 0;
        if ($pid > 0) {
            $flex['storagePid'] = $pid;
        }
        $this->conf = array_merge($this->conf, $flex);
    }

    /**
     * Loads a variable from the flexform
     *
     * @param string $var Name of variable
     * @param string $sheet Name of sheet
     * @return string Value of var
     */
    protected function flexFormValue($var, $sheet)
    {
        return $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $var, $sheet);
    }

    /**
     * Generate link with typolink function
     *
     * @param string $label Linktext
     * @param array $piVars Link vars
     * @param bool $returnUrl TRUE: returns only url  FALSE (default) returns the link)
     * @return string Link or url
     */
    protected function getPageLink($label, $piVars, $returnUrl = false)
    {
        $additionalParams = '';
        if (!empty($piVars)) {
            foreach ($piVars as $key => $val) {
                $additionalParams .= '&' . $key . '=' . $val;
            }
        }
        // Should GETvars be preserved?
        if ($this->conf['preserveGETvars']) {
            $additionalParams .= $this->getPreserveGetVars();
        }
        $this->conf['linkConfig.']['parameter'] = $this->frontendController->id;
        if ($additionalParams) {
            $this->conf['linkConfig.']['additionalParams'] = $additionalParams;
        }
        if ($returnUrl) {
            return htmlspecialchars($this->cObj->typoLink_URL($this->conf['linkConfig.']));
        } else {
            return $this->cObj->typoLink($label, $this->conf['linkConfig.']);
        }
    }

    /**
     * Add additional parameters for links according to TS setting preserveGETvars.
     * Possible values are "all" or a comma separated list of allowed GET-vars.
     * Supports multi-dimensional GET-vars.
     * Some hardcoded values are dropped.
     *
     * @return string additionalParams-string
     */
    protected function getPreserveGetVars()
    {
        $getVars = GeneralUtility::_GET();
        unset(
            $getVars['id'],
            $getVars['no_cache'],
            $getVars['logintype'],
            $getVars['redirect_url'],
            $getVars['cHash'],
            $getVars[$this->prefixId]
        );
        if ($this->conf['preserveGETvars'] === 'all') {
            $preserveQueryParts = $getVars;
        } else {
            $preserveQueryParts = GeneralUtility::trimExplode(',', $this->conf['preserveGETvars']);
            $preserveQueryParts = GeneralUtility::explodeUrl2Array(implode('=1&', $preserveQueryParts) . '=1', true);
            $preserveQueryParts = \TYPO3\CMS\Core\Utility\ArrayUtility::intersectRecursive($getVars, $preserveQueryParts);
        }
        $parameters = GeneralUtility::implodeArrayForUrl('', $preserveQueryParts);
        return $parameters;
    }

    /**
     * Is used by forgot password - function with md5 option.
     * @param int $len Length of new password
     * @return string New password
     */
    protected function generatePassword($len)
    {
        $pass = '';
        while ($len--) {
            $char = rand(0, 35);
            if ($char < 10) {
                $pass .= '' . $char;
            } else {
                $pass .= chr($char - 10 + 97);
            }
        }
        return $pass;
    }

    /**
     * Returns the header / message value from flexform if present, else from locallang.xlf
     *
     * @param string $label label name
     * @param array $stdWrapArray TS stdWrap array
     * @return string label text
     */
    protected function getDisplayText($label, $stdWrapArray = [])
    {
        $text = $this->flexFormValue($label, 's_messages') ? $this->cObj->stdWrap($this->flexFormValue($label, 's_messages'), $stdWrapArray) : $this->cObj->stdWrap($this->pi_getLL('ll_' . $label), $stdWrapArray);
        $replace = $this->getUserFieldMarkers();
        return strtr($text, $replace);
    }

    /**
     * Returns Array of markers filled with user fields
     *
     * @return array Marker array
     */
    protected function getUserFieldMarkers()
    {
        $marker = [];
        // replace markers with fe_user data
        if ($this->frontendController->fe_user->user) {
            // All fields of fe_user will be replaced, scheme is ###FEUSER_FIELDNAME###
            foreach ($this->frontendController->fe_user->user as $field => $value) {
                $marker['###FEUSER_' . GeneralUtility::strtoupper($field) . '###'] = $this->cObj->stdWrap($value, $this->conf['userfields.'][$field . '.']);
            }
            // Add ###USER### for compatibility
            $marker['###USER###'] = $marker['###FEUSER_USERNAME###'];
        }
        return $marker;
    }

    /**
     * Returns a valid and XSS cleaned url for redirect, checked against configuration "allowedRedirectHosts"
     *
     * @param string $url
     * @return string cleaned referer or empty string if not valid
     */
    protected function validateRedirectUrl($url)
    {
        $url = strval($url);
        if ($url === '') {
            return '';
        }
        $decodedUrl = rawurldecode($url);
        $sanitizedUrl = GeneralUtility::removeXSS($decodedUrl);
        if ($decodedUrl !== $sanitizedUrl || preg_match('#["<>\\\\]+#', $url)) {
            GeneralUtility::sysLog(sprintf($this->pi_getLL('xssAttackDetected'), $url), 'felogin', GeneralUtility::SYSLOG_SEVERITY_WARNING);
            return '';
        }
        // Validate the URL:
        if ($this->isRelativeUrl($url) || $this->isInCurrentDomain($url) || $this->isInLocalDomain($url)) {
            return $url;
        }
        // URL is not allowed
        GeneralUtility::sysLog(sprintf($this->pi_getLL('noValidRedirectUrl'), $url), 'felogin', GeneralUtility::SYSLOG_SEVERITY_WARNING);
        return '';
    }

    /**
     * Determines whether the URL is on the current host and belongs to the
     * current TYPO3 installation. The scheme part is ignored in the comparison.
     *
     * @param string $url URL to be checked
     * @return bool Whether the URL belongs to the current TYPO3 installation
     */
    protected function isInCurrentDomain($url)
    {
        $urlWithoutSchema = preg_replace('#^https?://#', '', $url);
        $siteUrlWithoutSchema = preg_replace('#^https?://#', '', GeneralUtility::getIndpEnv('TYPO3_SITE_URL'));
        return StringUtility::beginsWith($urlWithoutSchema . '/', GeneralUtility::getIndpEnv('HTTP_HOST') . '/')
            && StringUtility::beginsWith($urlWithoutSchema, $siteUrlWithoutSchema);
    }

    /**
     * Determines whether the URL matches a domain
     * in the sys_domain database table.
     *
     * @param string $url Absolute URL which needs to be checked
     * @return bool Whether the URL is considered to be local
     */
    protected function isInLocalDomain($url)
    {
        $result = false;
        if (GeneralUtility::isValidUrl($url)) {
            $parsedUrl = parse_url($url);
            if ($parsedUrl['scheme'] === 'http' || $parsedUrl['scheme'] === 'https') {
                $host = $parsedUrl['host'];
                // Removes the last path segment and slash sequences like /// (if given):
                $path = preg_replace('#/+[^/]*$#', '', $parsedUrl['path']);
                $localDomains = $this->databaseConnection->exec_SELECTgetRows('domainName', 'sys_domain', '1=1' . $this->cObj->enableFields('sys_domain'));
                if (is_array($localDomains)) {
                    foreach ($localDomains as $localDomain) {
                        // strip trailing slashes (if given)
                        $domainName = rtrim($localDomain['domainName'], '/');
                        if (GeneralUtility::isFirstPartOfStr($host . $path . '/', $domainName . '/')) {
                            $result = true;
                            break;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Determines whether the URL is relative to the
     * current TYPO3 installation.
     *
     * @param string $url URL which needs to be checked
     * @return bool Whether the URL is considered to be relative
     */
    protected function isRelativeUrl($url)
    {
        $parsedUrl = @parse_url($url);
        if ($parsedUrl !== false && !isset($parsedUrl['scheme']) && !isset($parsedUrl['host'])) {
            // If the relative URL starts with a slash, we need to check if it's within the current site path
            return $parsedUrl['path'][0] !== '/' || GeneralUtility::isFirstPartOfStr($parsedUrl['path'], GeneralUtility::getIndpEnv('TYPO3_SITE_PATH'));
        }
        return false;
    }
}
