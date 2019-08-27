.. include:: ../Includes.txt



.. _configuration:

=============
Configuration
=============

All configuration options are available in the FlexForm or TypoScript,
with the FlexForm settings taking precedence.


.. _plugin-tx-felogin-pi1:

plugin.tx\_felogin\_pi1
=======================

.. _storagepid:

storagePid
----------

.. container:: table-row

   Property
         storagePid

   Data type
         string

   Default
         {$styles.content.loginform.pid}

   Description
         Define the Storage Folder with the Website User Records, using a comma
         separated list or single value



.. _recursive:

recursive
---------

.. container:: table-row

   Property
         recursive

   Data type
         bool

   Description
         If set, also any subfolders of the storagePid will be used



.. _templatefile:

templateFile
------------

.. container:: table-row

   Property
         templateFile

   Data type
         string

   Default
         EXT:felogin/Resources/Private/Templates/FrontendLogin.html

   Description
         The Template File

         In TYPO3 6.2 the frontend form is not shown if you use
         css_styled_content version 4.5. In this case you must define the
         template in TypoScript constants:
         :code:`styles.content.loginform.templateFile = EXT:felogin/template.html`



.. _feloginbaseurl:

feloginBaseURL
--------------

.. container:: table-row

   Property
         feloginBaseURL

   Data type
         string

   Description
         Base url if something other than the system base URL is needed



.. _wrapcontentinbaseclass:

wrapContentInBaseClass
----------------------

.. container:: table-row

   Property
         wrapContentInBaseClass

   Data type
         bool

   Default
         true

   Description
         If set, plugin is wrapped by Standard Base Class-Wrap



.. _linkconfig:

linkConfig
----------

.. container:: table-row

   Property
         linkConfig

   Data type
         array

   Description
         Typolink Configuration for the generated Links



.. _preservegetvars:

preserveGETvars
---------------

.. container:: table-row

   Property
         preserveGETvars

   Data type
         String

   Description
         When using login plugin on a page with other plugins you might want to
         have your GET-params preserved. You can define them here. Possible
         settings:

         all - takes all GET-vars found

         comma-separated list - takes defined vars

         Example::

            preserveGETvars = tx_ttnews[tt_news],tx_myext[id],...



.. _showforgotpasswordlink:

showForgotPasswordLink
----------------------

.. container:: table-row

   Property
         showForgotPasswordLink

   Data type
         bool

   Description
         If set, the section in the template to display the link to the forget
         password dialogue is visible.

         .. important::

             Be aware that having this option disabled also prevents the plugin to
             display the forgot password form. For instance if you access the link
             directly.



.. _showpermalogin:

showPermaLogin
--------------

.. container:: table-row

   Property
         showPermaLogin

   Data type
         bool

   Description
         If set, the section in the template to display the option to remember
         the login (with a cookie) is visible.



.. _forgetlinkhashvalidtime:

forgetLinkHashValidTime
-----------------------

.. container:: table-row

   Property
         forgetLinkHashValidTime

   Data type
         integer

   Default
         12

   Description
         How many hours the link for forget password is valid



.. _newpasswordminlength:

newPasswordMinLength
--------------------

.. container:: table-row

   Property
         newPasswordMinLength

   Data type
         integer

   Default
         6

   Description
         Minimum length of the new password a user sets



.. _showlogoutformafterlogin:

showLogoutFormAfterLogin
------------------------

.. container:: table-row

   Property
         showLogoutFormAfterLogin

   Data type
         bool

   Description
         If set, the logout form will be displayed immediately after successful
         login.

         .. note::

            Setting this option will disable the redirect options!
            Instead of redirecting the plugin will show the logout form.



.. _welcomeheader-stdwrap:

welcomeHeader\_stdWrap
----------------------

.. container:: table-row

   Property
         welcomeHeader\_stdWrap

   Data type
         array

   Default
         wrap = <h3>\|</h3>

   Description
         stdWrap for Welcome Header



.. _welcomemessage-stdwrap:

welcomeMessage\_stdWrap
-----------------------

.. container:: table-row

   Property
         welcomeMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for Welcome Message



.. _successheader-stdwrap:

successHeader\_stdWrap
----------------------

.. container:: table-row

   Property
         successHeader\_stdWrap

   Data type
         array

   Default
         wrap = <h3>\|</h3>

   Description
         stdWrap for Login SuccessHeader



.. _successmessage-stdwrap:

successMessage\_stdWrap
-----------------------

.. container:: table-row

   Property
         successMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for Login Success Message



.. _logoutheader-stdwrap:

logoutHeader\_stdWrap
---------------------

.. container:: table-row

   Property
         logoutHeader\_stdWrap

   Data type
         array

   Default
         wrap = <h3>\|</h3>

   Description
         stdWrap for Logout Header



.. _logoutmessage-stdwrap:

logoutMessage\_stdWrap
----------------------

.. container:: table-row

   Property
         logoutMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for Logout Message



.. _errorheader-stdwrap:

errorHeader\_stdWrap
--------------------

.. container:: table-row

   Property
         errorHeader\_stdWrap

   Data type
         array

   Default
         wrap = <h3>\|</h3>

   Description
         stdWrap for Error Header



.. _errormessage-stdwrap:

errorMessage\_stdWrap
---------------------

.. container:: table-row

   Property
         errorMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for Error Message



.. _forgotheader-stdwrap:

forgotHeader\_stdWrap
---------------------

.. container:: table-row

   Property
         forgotHeader\_stdWrap

   Data type
         array

   Default
         wrap = <h3>\|</h3>

   Description
         stdWrap for Forgot Header



.. _forgotmessage-stdwrap:

forgotMessage\_stdWrap
----------------------

.. container:: table-row

   Property
         forgotMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for Forgot Message



.. _forgoterrormessage-stdwrap:

forgotErrorMessage\_stdWrap
---------------------------

.. container:: table-row

   Property
         forgotErrorMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for error message in forgot password form



.. _forgotresetmessageemailsentmessage-stdwrap:

forgotResetMessageEmailSentMessage\_stdWrap
-------------------------------------------

.. container:: table-row

   Property
         forgotResetMessageEmailSentMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for message that password reset mail was sent



.. _changepasswordnotvalidmessage-stdwrap:

changePasswordNotValidMessage\_stdWrap
--------------------------------------

.. container:: table-row

   Property
         changePasswordNotValidMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for message that changed password was not valid



.. _changepasswordtooshortmessage-stdwrap:

changePasswordTooShortMessage\_stdWrap
--------------------------------------

.. container:: table-row

   Property
         changePasswordTooShortMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for message that new password was too short



.. _changepasswordnotequalmessage-stdwrap:

changePasswordNotEqualMessage\_stdWrap
--------------------------------------

.. container:: table-row

   Property
         changePasswordNotEqualMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for message that new passwords were not equal



.. _changepasswordheader-stdwrap:

changePasswordHeader\_stdWrap
-----------------------------

.. container:: table-row

   Property
         changePasswordHeader\_stdWrap

   Data type
         array

   Default
         wrap = <h3>\|</h3>

   Description
         stdWrap for Change Password Header



.. _changepasswordmessage-stdwrap:

changePasswordMessage\_stdWrap
------------------------------

.. container:: table-row

   Property
         changePasswordMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for Change Password Message



.. _changepassworddonemessage-stdwrap:

changePasswordDoneMessage\_stdWrap
----------------------------------

.. container:: table-row

   Property
         changePasswordDoneMessage\_stdWrap

   Data type
         array

   Default
         wrap = <div>\|</div>

   Description
         stdWrap for message that password was changed



.. _userfields:

userfields
----------

.. container:: table-row

   Property
         userfields

   Data type
         array

   Description
         Array of fields from the fe\_users table. Each field can have its own
         stdWrap configuration. These fields can be used as markers in the
         template (e.g. ###FEUSER\_USERNAME###)

         Example:

         .. code-block:: typoscript

            username {
                htmlSpecialChars = 1
                wrap = <strong>\|</strong>
            }



.. _redirectmode:

redirectMode
------------

.. container:: table-row

   Property
         redirectMode

   Data type
         string

   Description
         Comma separated list of redirect modes. Possible values:

         groupLogin, userLogin, login, getpost, referer, refererDomains,
         loginError, logout

         See section on redirect modes for details.



.. _redirectfirstmethod:

redirectFirstMethod
-------------------

.. container:: table-row

   Property
         redirectFirstMethod

   Data type
         bool

   Description
         If set the first method from redirectMode which is possible will be
         used



.. _redirectpagelogin:

redirectPageLogin
-----------------

.. container:: table-row

   Property
         redirectPageLogin

   Data type
         integer

   Description
         Page id to redirect to after Login



.. _redirectpageloginerror:

redirectPageLoginError
----------------------

.. container:: table-row

   Property
         redirectPageLoginError

   Data type
         integer

   Description
         Page id to redirect to after Login Error



.. _redirectpagelogout:

redirectPageLogout
------------------

.. container:: table-row

   Property
         redirectPageLogout

   Data type
         integer

   Description
         Page id to redirect to after Logout



.. _redirectdisable:

redirectDisable
---------------

.. container:: table-row

   Property
         redirectDisable

   Data type
         bool

   Description
         If set redirecting is disabled



.. _email-from:

email\_from
-----------

.. container:: table-row

   Property
         email\_from

   Data type
         string

   Description
         Email address used as sender of the change password emails



.. _email-fromname:

email\_fromName
---------------

.. container:: table-row

   Property
         email\_fromName

   Data type
         string

   Description
         Name used as sender of the change password emails



.. _replyto:

replyTo
-------

.. container:: table-row

   Property
         replyTo

   Data type
         string

   Description
         Reply-to address used in the change password emails



.. _domains:

domains
-------

.. container:: table-row

   Property
         domains

   Data type
         string

   Description
         Comma separated list of domains which are allowed for the referrer
         redirect mode



.. _dateformat:

dateFormat
----------

.. container:: table-row

   Property
         dateFormat

   Data type
         date-conf

   Default
         Y-m-d H:i

   Description
         Format for the link is valid until message (forget password email)



.. _linkprefix:

linkPrefix
----------

.. container:: table-row

   Property
         linkPrefix

   Data type
         string

   Description
         Prefix for the link sent in the forgot password email



.. _exposenonexistentuserinforgotpassworddialog:

exposeNonexistentUserInForgotPasswordDialog
-------------------------------------------

.. container:: table-row

   Property
         exposeNonexistentUserInForgotPasswordDialog

   Data type
         bool

   Default
         0

   Description
         If set and the user account cannot be found in the forgot password
         dialogue, an error message will be shown that the account could not be
         found.

         .. warning::

            Enabling this will disclose information about whether an
            email address is actually used for a frontend user account! Visitors
            can find out if a user is known as frontend user.



.. _css-default-style:

\_CSS\_DEFAULT\_STYLE
---------------------

.. container:: table-row

   Property
         \_CSS\_DEFAULT\_STYLE

   Data type
         string

   Description
         CSS included in the page containing the login form

         Example:

         .. code-block:: typoscript

            .tx-felogin-pi1 label {
                display: block;
            }


.. _default-pi-vars:

\_DEFAULT\_PI\_VARS
-------------------

.. container:: table-row

   Property
         \_DEFAULT\_PI\_VARS

   Data type
         array

   Description
         Default values for variables sent from the forms.


.. _local-lang:

\_LOCAL\_LANG
-------------

.. container:: table-row

   Property
         \_LOCAL\_LANG (+ "." + "default" or language code)

   Data type
         array

   Description
         Localized labels that can be overridden per TypoScript.

         =========================================== =================================
         Label                                       Usage
         =========================================== =================================
         ll\_welcome\_header                         Status header
         ll\_welcome\_message                        Status message
         ll\_logout\_header                          Status header
         ll\_logout\_message                         Status message
         ll\_error\_header                           Status header
         ll\_error\_message                          Status message
         ll\_success\_header                         Status header
         ll\_success\_message                        Status message
         ll\_status\_header                          Status header
         ll\_status\_message                         Status message
         cookie\_warning                             Warning when no cookie can be set
         username                                    Form field label
         password                                    Form field label
         login                                       Legend, form field label
         permalogin                                  Form field label
         logout                                      Legend, submit button
         send\_password                              Submit button
         reset\_password                             Legend, submit button
         ll\_change\_password\_header                Status header
         ll\_change\_password\_message               Status message
         ll\_change\_password\_nolinkprefix\_message Error message
         ll\_change\_password\_notvalid\_message     Status message
         ll\_change\_password\_notequal\_message     Status message
         ll\_change\_password\_tooshort\_message     Status message
         ll\_change\_password\_done\_message         Status message
         change\_password                            Legend
         newpassword\_label1                         Form field label
         newpassword\_label2                         Form field label
         your\_email                                 Form field label
         ll\_forgot\_header                          Status header, link text
         ll\_forgot\_validate\_reset\_password       Email body
         ll\_forgot\_message\_emailSent              Status message
         ll\_forgot\_reset\_message                  Status message
         ll\_forgot\_reset\_message\_emailSent       Status message
         ll\_forgot\_reset\_message\_error           Status message
         ll\_forgot\_header\_backToLogin             Text of back link to loginform
         ll\_enter\_your\_data                       Form field label
         oLabel\_header\_welcome                     Legend
         =========================================== =================================
