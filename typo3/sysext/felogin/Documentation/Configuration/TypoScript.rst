:navigation-title: TypoScript

..  include:: /Includes.rst.txt
..  _configuration-typoscript:

==============================================
TypoScript configuration of the Frontend Login
==============================================

..  contents::
    :caption: Content on this page
    :depth: 1

..  _plugin-tx-felogin-login:

TypoScript setup / FlexForm settings
====================================

Most of these plugin settings can be set with the following methods, the top
bottom most taking precedence:

..  include:: _SettingsOrder.rst.txt

See also :ref:`configuration-examples-flexform`.

..  confval-menu::
    :name: typoscript
    :display: table
    :type:
    :Site set setting:

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
        :Site set setting: :confval:`felogin.pid <felogin-felogin-pid>`
        :TypoScript Constant: {$styles.content.loginform.pid}

        Define the User Storage Page with the Website User Records, using a
        comma separated list or a single value (page id).

    ..  _recursive:

    ..  confval:: recursive
        :name: typoscript-recursive
        :type: int
        :Site set setting: :confval:`felogin.recursive <felogin-felogin-recursive>`
        :TypoScript Constant: {$styles.content.loginform.recursive}

        If set, also any subfolders of the User Storage Page will be used
        at configured recursive levels

    ..  _redirectmode:

    ..  confval:: redirectMode
        :name: typoscript-redirectMode
        :type: string
        :Site set setting: :confval:`felogin.redirectMode <felogin-felogin-redirectmode>`
        :TypoScript Constant: {$styles.content.loginform.redirectMode}

        Comma separated list of redirect modes. Possible values:
        ``groupLogin``, ``userLogin``, ``login``, ``getpost``, ``referer``,
        ``refererDomains``, ``loginError``, ``logout``
        See section on redirect modes for details.

    ..  _redirectfirstmethod:

    ..  confval:: redirectFirstMethod
        :name: typoscript-redirectFirstMethod
        :type: bool
        :Site set setting: :confval:`felogin.redirectFirstMethod <felogin-felogin-redirectfirstmethod>`
        :TypoScript Constant: {$styles.content.loginform.redirectFirstMethod}

        If set the first method from redirectMode which is possible will be
        used

    ..  _redirectpagelogin:

    ..  confval:: redirectPageLogin
        :name: typoscript-redirectPageLogin
        :type: integer
        :Site set setting: :confval:`felogin.redirectPageLogin <felogin-felogin-redirectpagelogin>`
        :TypoScript Constant: {$styles.content.loginform.redirectPageLogin}

        Page id to redirect to after Login

    ..  _redirectpageloginerror:

    ..  confval:: redirectPageLoginError
        :name: typoscript-redirectPageLoginError
        :type: integer
        :Site set setting: :confval:`felogin.redirectPageLoginError <felogin-felogin-redirectpageloginerror>`
        :TypoScript Constant: {$styles.content.loginform.redirectPageLoginError}

        Page id to redirect to after Login Error

    ..  _redirectpagelogout:

    ..  confval:: redirectPageLogout
        :name: typoscript-redirectPageLogout
        :type: integer
        :Site set setting:
        :TypoScript Constant: {$styles.content.loginform.redirectPageLogout}

        Page id to redirect to after Logout

    ..  _redirectdisable:

    ..  confval:: redirectDisable
        :name: typoscript-redirectDisable
        :type: bool
        :Site set setting: :confval:`felogin.redirectPageLogout <felogin-felogin-redirectpagelogout>`
        :TypoScript Constant: {$styles.content.loginform.redirectDisable}

        If set redirecting is disabled

    ..  _dateformat:

    ..  confval:: dateFormat
        :name: typoscript-dateFormat
        :type: date-conf
        :Site set setting: :confval:`felogin.dateFormat <felogin-felogin-dateformat>`
        :TypoScript Constant: Y-m-d H:i

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
            :Site set setting: :confval:`felogin.email.templateName <felogin-felogin-email-templatename>`
            :TypoScript Constant: {$styles.content.loginform.email.templateName}

            Template name for emails. Plaintext emails get the .txt file extension.

        ..  confval:: email.layoutRootPaths
            :name: typoscript-email.layoutRootPaths
            :type: array
            :Site set setting: :confval:`felogin.email.templateRootPath <felogin-felogin-email-templaterootpath>`
            :TypoScript Constant: {$styles.content.loginform.email.layoutRootPath}

            Path to layout directory used for emails

        ..  confval:: email.templateRootPaths
            :name: typoscript-email.templateRootPaths
            :type: array
            :Site set setting: :confval:`felogin.email.templateRootPath <felogin-felogin-email-templaterootpath>`
            :TypoScript Constant: {$styles.content.loginform.email.templateRootPaths}

            Path to template directory used for emails

        ..  confval:: email.partialRootPaths
            :name: typoscript-email.partialRootPaths
            :type: array
            :Site set setting: :confval:`felogin.email.partialRootPath <felogin-felogin-email-partialrootpath>`
            :TypoScript Constant: {$styles.content.loginform.email.partialRootPaths}

            Path to partial directory used for emails

    ..  confval:: forgotLinkHashValidTime
        :name: typoscript-forgotLinkHashValidTime
        :type: integer
        :Site set setting: :confval:`felogin.forgotLinkHashValidTime <felogin-felogin-forgotlinkhashvalidtime>`
        :TypoScript Constant: {$styles.content.loginform.forgotLinkHashValidTime}

        Time in hours how long the link for forgot password is valid

    ..  _domains:

    ..  confval:: domains
        :name: typoscript-domains
        :type: string

        Comma separated list of domains which are allowed for the referrer
        redirect mode


..  _configuration-examples-typoscript-constant:

Example: Set the default storage page via TypoScript constant
=============================================================

You can use the :ref:`TypoScript provider <t3coreapi:site-sets-typoscript>`
or other means of :ref:`setting the TypoScript constants <t3tsref:using-and-setting>`.

..  versionchanged:: 13.1
    It is recommended to use the :ref:`configuration-site-set-settings`
    instead, as TypoScript constants will be phased out in the future.

..  literalinclude:: _constants.typoscript
    :caption: config/sites/MySite/constants.typoscript

..  _configuration-examples-typoscript:

Example: Set the default storage page via TypoScript setup
==========================================================

In order to set the default storage page to a more dynamic value, use
the TypoScript setup. Use the :ref:`TypoScript provider <t3coreapi:site-sets-typoscript>`
or other means of ref:`setting the TypoScript setup <t3tsref:using-and-setting>`.

..  literalinclude:: _setup.typoscript
    :caption: config/sites/MySite/constants.typoscript

..  _configuration-examples-flexform:

Example: Override the default storage page in the plugin's FlexForm
===================================================================

If you set any FlexForm setting within the content element representing the
plugin to a **non-empty value** it will override any other setting not matter if it
is made via site settings, TypoScript constant ot TypoScript setup. Empty values
take no effect if a default was set by other means.

In the backend module :guilabel:`Web > Page` edit the content element containing
the login form. Go to tab :guilabel:`Plugin` and sub tab :guilabel:`General`.
You should see a form similar to the following:

..  figure:: /Images/GeneralSettings.png
    :alt: A screenshot showing the "General" tab of the plugin settings

    Settings in the tab :guilabel:`General` of the plugin tab

Choose the desired page or pages in the field with label
:guilabel:`User Storage Page` (key :confval:`settings.pages <typoscript-pages>`).

..  tip::
    It is sometimes hard to determine, which label in the FlexForm corresponds
    to which key in the :ref:`FlexForm reference <plugin-tx-felogin-login>`.

    Turn on the :confval:`backend debug mode <t3coreapi:globals-typo3-conf-vars-be-debug>`
    to get a visual hint in the backend for the keys of the FlexForm field.

..  figure:: /Images/FlexFormKey.png
    :alt: A screenshot showing FlexForm Field with key `settings.pages`

    The corresponding FlexForm field :confval:`settings.pages <typoscript-pages>`
    in backend debug mode.
