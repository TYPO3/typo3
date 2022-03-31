.. include:: /Includes.rst.txt



.. _configuration:

=============
Configuration
=============

All configuration options are available in the FlexForm or TypoScript,
with the FlexForm settings taking precedence.


.. _plugin-tx-felogin-login:

plugin.tx\_felogin\_login.settings
==================================


.. _showforgotpassword:

showForgotPassword
------------------

.. container:: table-row

   Property
         showForgotPassword

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



.. _pages:

pages
-----

.. container:: table-row

   Property
         pages

   Data type
         string

   Default
         {$styles.content.loginform.pid}

   Description
         Define the User Storage Page with the Website User Records, using a
         comma separated list or single value



.. _recursive:

recursive
---------

.. container:: table-row

   Property
         recursive

   Data type
         int

   Default
         {$styles.content.loginform.recursive}

   Description
         If set, also any subfolders of the User Storage Page will be used
         at configured recursive levels



.. _redirectmode:

redirectMode
------------

.. container:: table-row

   Property
         redirectMode

   Data type
         string

   Default
         {$styles.content.loginform.redirectMode}

   Description
         Comma separated list of redirect modes. Possible values:

         ``groupLogin``, ``userLogin``, ``login``, ``getpost``, ``referer``,
         ``refererDomains``, ``loginError``, ``logout``

         See section on redirect modes for details.



.. _redirectfirstmethod:

redirectFirstMethod
-------------------

.. container:: table-row

   Property
         redirectFirstMethod

   Data type
         bool

   Default
         {$styles.content.loginform.redirectFirstMethod}

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

   Default
         {$styles.content.loginform.redirectPageLogin}

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

   Default
         {$styles.content.loginform.redirectPageLoginError}

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

   Default
         {$styles.content.loginform.redirectPageLogout}

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

   Default
         {$styles.content.loginform.redirectDisable}

   Description
         If set redirecting is disabled



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



email.templateName
------------------

.. container:: table-row

   Property
         email.templateName

   Data type
         string

   Default
         {$styles.content.loginform.email.templateName}

   Description
         Template name for emails. Plaintext emails get the .txt file extension.



email.layoutRootPaths
---------------------

.. container:: table-row

   Property
         email.layoutRootPaths

   Data type
         array

   Default
         {$styles.content.loginform.email.layoutRootPath}

   Description
         Path to layout directory used for emails



email.templateRootPaths
-----------------------

.. container:: table-row

   Property
         email.templateRootPaths

   Data type
         array

   Default
         {$styles.content.loginform.email.templateRootPaths}

   Description
         Path to template directory used for emails



email.partialRootPaths
----------------------

.. container:: table-row

   Property
         email.partialRootPaths

   Data type
         array

   Default
         {$styles.content.loginform.email.partialRootPaths}

   Description
         Path to partial directory used for emails



exposeNonexistentUserInForgotPasswordDialog
-------------------------------------------

.. container:: table-row

   Property
         exposeNonexistentUserInForgotPasswordDialog

   Data type
         bool

   Default
         {$styles.content.loginform.exposeNonexistentUserInForgotPasswordDialog}

   Description
         If set and the user account cannot be found in the forgot password
         dialogue, an error message will be shown that the account could not be
         found.

         .. warning::

            Enabling this will disclose information about whether an
            email address is actually used for a frontend user account! Visitors
            can find out if a user is known as frontend user.



forgotLinkHashValidTime
-----------------------

.. container:: table-row

   Property
         forgotLinkHashValidTime

   Data type
         integer

   Default
         {$styles.content.loginform.forgotLinkHashValidTime}

   Description
         Time in hours how long the link for forget password is valid



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



passwordValidators
------------------

.. container:: table-row

   Property
         passwordValidators

   Data type
         array

   Description
         Array of validators to use for the new user password.
