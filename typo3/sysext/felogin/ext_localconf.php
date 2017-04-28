<?php
defined('TYPO3_MODE') or die();

// Add a default TypoScript for the CType "login"
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptConstants(
    '
# customsubcategory=01_Storage=Storage
# customsubcategory=02_Template=Template
# customsubcategory=03_Features=Features
# customsubcategory=04_EMail=E-Mail
# customsubcategory=05_Redirects=Redirects
# customsubcategory=06_Security=Security

styles.content.loginform {
    # cat=Frontend Login/01_Storage/100; type=int+; label= Storage Folder: Define the Storage Folder with the Website User Records, using a comma separated list or single value
    pid = 0
    # cat=Frontend Login/01_Storage/101; type=boolean; label= Recursive: If set, also any subfolders of the storagePid will be used
    recursive = 0

    # cat=Frontend Login/02_Template/100; type=string; label= Login template: Enter the path for the HTML template to be used
    templateFile = EXT:felogin/Resources/Private/Templates/FrontendLogin.html
    # cat=Frontend Login/02_Template/101; type=string; label= BaseURL for generated links: Base url if something other than the system base URL is needed
    feloginBaseURL =
    # cat=Frontend Login/02_Template/102; type=string; label= Date format: Format for the link is valid until message (forget password email)
    dateFormat = Y-m-d H:i

    # cat=Frontend Login/03_Features/100; type=boolean; label= Display Password Recovery Link: If set, the section in the template to display the link to the forget password dialogue is visible.
    showForgotPasswordLink = 0
    # cat=Frontend Login/03_Features/101; type=boolean; label= Display Remember Login Option: If set, the section in the template to display the option to remember the login (with a cookie) is visible.
    showPermaLogin = 0
    # cat=Frontend Login/03_Features/102; type=boolean; label= Disable redirect after successful login, but display logout-form: If set, the logout form will be displayed immediately after successful login.
    showLogoutFormAfterLogin = 0

    # cat=Frontend Login/04_EMail/100; type=string; label= E-Mail Sender Address: E-Mail address used as sender of the change password emails
    emailFrom =
    # cat=Frontend Login/04_EMail/101; type=string; label= E-Mail Sender Name: Name used as sender of the change password emails
    emailFromName =
    # cat=Frontend Login/04_EMail/102; type=string; label= Reply To E-Mail Address: Reply-to address used in the change password emails
    replyToEmail =

    # cat=Frontend Login/05_Redirects/101; type=string; label= Redirect Mode: Comma separated list of redirect modes. Possible values: groupLogin, userLogin, login, getpost, referer, refererDomains, loginError, logout
    redirectMode =
    # cat=Frontend Login/05_Redirects/102; type=boolean; label= Use First Supported Mode from Selection: If set the first method from redirectMode which is possible will be used
    redirectFirstMethod = 0
    # cat=Frontend Login/05_Redirects/103; type=int+; label= After Successful Login Redirect to Page: Page id to redirect to after Login
    redirectPageLogin = 0
    # cat=Frontend Login/05_Redirects/104; type=int+; label= After Failed Login Redirect to Page: Page id to redirect to after Login Error
    redirectPageLoginError = 0
    # cat=Frontend Login/05_Redirects/105; type=int+; label= After Logout Redirect to Page: Page id to redirect to after Logout
    redirectPageLogout = 0
    # cat=Frontend Login/05_Redirects/106; type=boolean; label= Disable Redirect: If set redirecting is disabled
    redirectDisable = 0

    # cat=Frontend Login/06_Security/100; type=int+; label= Time in hours how long the link for forget password is valid: How many hours the link for forget password is valid
    forgotLinkHashValidTime = 12
    # cat=Frontend Login/06_Security/101; type=int+; label= Minimum amount of characters, when setting a new password: Minimum length of the new password a user sets
    newPasswordMinLength = 6
    # cat=Frontend Login/06_Security/102; type=string; label= Allowed Referrer-Redirect-Domains: Comma separated list of domains which are allowed for the referrer redirect mode
    domains =
    # cat=Frontend Login/06_Security/103; type=boolean; label= Expose existing users: Expose the information on whether or not the account for which a new password was requested exists. By default, that information is not disclosed for privacy reasons.
    exposeNonexistentUserInForgotPasswordDialog = 0
}
    '
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup(
    '
# Setting "felogin" plugin TypoScript
plugin.tx_felogin_pi1 = USER_INT
plugin.tx_felogin_pi1 {
    userFunc = TYPO3\CMS\Felogin\Controller\FrontendLoginController->main

    # Storage
    storagePid = {$styles.content.loginform.pid}
    recursive = {$styles.content.loginform.recursive}

    # Template
    templateFile = {$styles.content.loginform.templateFile}
    feloginBaseURL = {$styles.content.loginform.feloginBaseURL}
    dateFormat = {$styles.content.loginform.dateFormat}

    # Features
    showForgotPasswordLink = {$styles.content.loginform.showForgotPasswordLink}
    showPermaLogin = {$styles.content.loginform.showPermaLogin}
    showLogoutFormAfterLogin = {$styles.content.loginform.showLogoutFormAfterLogin}

    # E-Mail Settings
    email_from = {$styles.content.loginform.emailFrom}
    email_fromName = {$styles.content.loginform.emailFromName}
    replyTo = {$styles.content.loginform.replyToEmail}

    # Redirects
    redirectMode = {$styles.content.loginform.redirectMode}
    redirectFirstMethod = {$styles.content.loginform.redirectFirstMethod}
    redirectPageLogin = {$styles.content.loginform.redirectPageLogin}
    redirectPageLoginError = {$styles.content.loginform.redirectPageLoginError}
    redirectPageLogout = {$styles.content.loginform.redirectPageLogout}
    redirectDisable = {$styles.content.loginform.redirectDisable}

    # Security
    forgotLinkHashValidTime = {$styles.content.loginform.forgotLinkHashValidTime}
    newPasswordMinLength = {$styles.content.loginform.newPasswordMinLength}
    domains = {$styles.content.loginform.domains}
    exposeNonexistentUserInForgotPasswordDialog = {$styles.content.loginform.exposeNonexistentUserInForgotPasswordDialog}

    # should a wrapper class be set for this content element
    wrapContentInBaseClass = 1

    # typolink-configuration for links / urls
    # parameter and additionalParams are set by extension
    linkConfig {
        target =
        ATagParams = rel="nofollow"
    }

    # preserve GET vars - define "all" or comma separated list of GET-vars that should be included by link generation
    preserveGETvars = all

    welcomeHeader_stdWrap {
        required = 1
        wrap = <h3>|</h3>
        htmlSpecialChars = 1
    }
    successHeader_stdWrap < .welcomeHeader_stdWrap
    logoutHeader_stdWrap < .welcomeHeader_stdWrap
    errorHeader_stdWrap < .welcomeHeader_stdWrap
    forgotHeader_stdWrap < .welcomeHeader_stdWrap
    changePasswordHeader_stdWrap < .welcomeHeader_stdWrap

    welcomeMessage_stdWrap {
        required = 1
        wrap = <div>|</div>
        htmlSpecialChars = 1
    }
    successMessage_stdWrap < .welcomeMessage_stdWrap
    logoutMessage_stdWrap < .welcomeMessage_stdWrap
    errorMessage_stdWrap < .welcomeMessage_stdWrap
    forgotMessage_stdWrap < .welcomeMessage_stdWrap
    forgotErrorMessage_stdWrap < .welcomeMessage_stdWrap
    forgotResetMessageEmailSentMessage_stdWrap < .welcomeMessage_stdWrap
    changePasswordNotValidMessage_stdWrap < .welcomeMessage_stdWrap
    changePasswordTooShortMessage_stdWrap < .welcomeMessage_stdWrap
    changePasswordNotEqualMessage_stdWrap < .welcomeMessage_stdWrap
    changePasswordMessage_stdWrap < .welcomeMessage_stdWrap
    changePasswordDoneMessage_stdWrap < .welcomeMessage_stdWrap

    cookieWarning_stdWrap {
        required = 1
        wrap = <p style="color:red; font-weight:bold;">|</p>
        htmlSpecialChars = 1
    }

    # stdWrap for fe_users fields used in Messages
    userfields {
        username {
            htmlSpecialChars = 1
            wrap = <strong>|</strong>
        }
    }
}

# Setting "felogin" plugin TypoScript
tt_content.login =< lib.contentElement
tt_content.login {
    templateName = Generic
    variables {
        content =< plugin.tx_felogin_pi1
    }
}
    '
);

// Add login to new content element wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
    mod.wizards.newContentElement.wizardItems.forms {
        elements.login {
            iconIdentifier = content-elements-login
            title = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:forms_login_title
            description = LLL:EXT:backend/Resources/Private/Language/locallang_db_new_content_el.xlf:forms_login_description
            tt_content_defValues {
                CType = login
            }
        }
        show :=addToList(login)
    }
');

// Page module hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['felogin'] = \TYPO3\CMS\Felogin\Hooks\CmsLayout::class;
