..  include:: /Includes.rst.txt
..  _configuration:

=============
Configuration
=============

All configuration options are available in the FlexForm or TypoScript,
with the FlexForm settings taking precedence.

..  _plugin-tx-felogin-login:

TypoScript setup / FlexForm settings
====================================

..  confval-menu::
    :name: typoscript
    :display: table
    :type:
    :default:

    ..  _showforgotpassword:

    ..  confval:: showForgotPassword
        :name: typoscript-showForgotPassword
        :type: bool

        If set, the section in the template to display the link to the forgot
        password dialogue is visible.

        ..  important::
             Be aware that having this option disabled also prevents the plugin to
             display the forgot password form. For instance if you access the link
             directly.

    ..  _showpermalogin:

    ..  confval:: showPermaLogin
        :name: typoscript-showPermaLogin
        :type: bool

        If set, the section in the template to display the option to remember
        the login (with a cookie) is visible.

    ..  _showlogoutformafterlogin:

    ..  confval:: showLogoutFormAfterLogin
        :name: typoscript-showLogoutFormAfterLogin
        :type: bool

        If set, the logout form will be displayed immediately after successful
        login.
        ..  note::
            Setting this option will disable the redirect options!
            Instead of redirecting the plugin will show the logout form.

    ..  _pages:

    ..  confval:: pages
        :name: typoscript-pages
        :type: string
        :default: {$styles.content.loginform.pid}

        Define the User Storage Page with the Website User Records, using a
        comma separated list or single value

    ..  _recursive:

    ..  confval:: recursive
        :name: typoscript-recursive
        :type: int
        :default: {$styles.content.loginform.recursive}

        If set, also any subfolders of the User Storage Page will be used
        at configured recursive levels

    ..  _redirectmode:

    ..  confval:: redirectMode
        :name: typoscript-redirectMode
        :type: string
        :default: {$styles.content.loginform.redirectMode}

        Comma separated list of redirect modes. Possible values:
        ``groupLogin``, ``userLogin``, ``login``, ``getpost``, ``referer``,
        ``refererDomains``, ``loginError``, ``logout``
        See section on redirect modes for details.

    ..  _redirectfirstmethod:

    ..  confval:: redirectFirstMethod
        :name: typoscript-redirectFirstMethod
        :type: bool
        :default: {$styles.content.loginform.redirectFirstMethod}

        If set the first method from redirectMode which is possible will be
        used

    ..  _redirectpagelogin:

    ..  confval:: redirectPageLogin
        :name: typoscript-redirectPageLogin
        :type: integer
        :default: {$styles.content.loginform.redirectPageLogin}

        Page id to redirect to after Login

    ..  _redirectpageloginerror:

    ..  confval:: redirectPageLoginError
        :name: typoscript-redirectPageLoginError
        :type: integer
        :default: {$styles.content.loginform.redirectPageLoginError}

        Page id to redirect to after Login Error

    ..  _redirectpagelogout:

    ..  confval:: redirectPageLogout
        :name: typoscript-redirectPageLogout
        :type: integer
        :default: {$styles.content.loginform.redirectPageLogout}

        Page id to redirect to after Logout

    ..  _redirectdisable:

    ..  confval:: redirectDisable
        :name: typoscript-redirectDisable
        :type: bool
        :default: {$styles.content.loginform.redirectDisable}

        If set redirecting is disabled

    ..  _dateformat:

    ..  confval:: dateFormat
        :name: typoscript-dateFormat
        :type: date-conf
        :default: Y-m-d H:i

        Format for the link is valid until message (forgot password email)

    ..  _email-from:

    ..  confval:: email_from
        :name: typoscript-email-from
        :type: string

        Email address used as sender of the change password emails

    ..  _email-fromname:

    ..  confval:: email_fromName
        :name: typoscript-email-fromName
        :type: string

        Name used as sender of the change password emails

    ..  confval:: email
        :name: typoscript-email

        ..  confval:: email.templateName
            :name: typoscript-email.templateName
            :type: string
            :default: {$styles.content.loginform.email.templateName}

            Template name for emails. Plaintext emails get the .txt file extension.

        ..  confval:: email.layoutRootPaths
            :name: typoscript-email.layoutRootPaths
            :type: array
            :default: {$styles.content.loginform.email.layoutRootPath}

            Path to layout directory used for emails

        ..  confval:: email.templateRootPaths
            :name: typoscript-email.templateRootPaths
            :type: array
            :default: {$styles.content.loginform.email.templateRootPaths}

            Path to template directory used for emails

        ..  confval:: email.partialRootPaths
            :name: typoscript-email.partialRootPaths
            :type: array
            :default: {$styles.content.loginform.email.partialRootPaths}

            Path to partial directory used for emails

    ..  confval:: exposeNonexistentUserInForgotPasswordDialog
        :name: typoscript-exposeNonexistentUserInForgotPasswordDialog
        :type: bool
        :default: {$styles.content.loginform.exposeNonexistentUserInForgotPasswordDialog}

        If set and the user account cannot be found in the forgot password
        dialogue, an error message will be shown that the account could not be
        found.
        ..  warning::
            Enabling this will disclose information about whether an
            email address is actually used for a frontend user account! Visitors
            can find out if a user is known as frontend user.

    ..  confval:: forgotLinkHashValidTime
        :name: typoscript-forgotLinkHashValidTime
        :type: integer
        :default: {$styles.content.loginform.forgotLinkHashValidTime}

        Time in hours how long the link for forgot password is valid

    ..  _domains:

    ..  confval:: domains
        :name: typoscript-domains
        :type: string

        Comma separated list of domains which are allowed for the referrer
        redirect mode

    ..  confval:: passwordValidators
        :name: typoscript-passwordValidators
        :type: array

        .. deprecated:: 12.3
        
           The TypoScript does not include validators any more by default. Instead, the
           extension uses global :ref:`password policies <t3coreapi:password-policies>`
           to ensure password requirements are fulfilled.

        Array of validators to use for the new user password.

        ..  rubric:: Migration

        Special password requirements configured using custom validators in
        TypoScript must be migrated to a custom password policy validator as
        described in :ref:`password policies <t3coreapi:password-policies>`.

        Before creating a custom password policy validator, it is recommended to
        evaluate, if the :php:`CorePasswordValidator` used in the default
        password policy suits current password requirements.
