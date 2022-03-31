.. include:: /Includes.rst.txt

================================================================
Feature: #89513 - Password Reset Functionality For Backend Users
================================================================

See :issue:`89513`

Description
===========

It is now possible for TYPO3 Backend users who use the default username / password
mechanism to log in, to reset their password by triggering an email through the
Login form.

The reset link is only shown if there is at least one user that matches the
following criteria:

* The user has a password entered previously (used to indicate that no third-party login was used)
* The user has a valid email added to their user record
* The user is neither deleted nor disabled
* The email address is only used once among all Backend users of the instance

Once the user has entered their email address, an email is sent out with a
link to set a new password which needs to have a least 8 characters.

The link is valid for 2 hours, and a token is added to the link.

If the password was provided correctly, it is updated for the user and can log-in.

Some notes on security:

* When having multiple users with the same email address, no reset functionality is provided
* No information disclosure is built-in, so if the email address is not in the system, it is not known to the outside
* Rate limiting is activated for allowing three emails to be sent within 30 minutes per email address
* Tokens are stored for the backend users in the database but hashed again just like the password
* When a user has logged in successfully (e.g. because he/she remembered the password) the token is removed from the database, effectively invalidating all existing email links

The feature is active by default and can be deactivated completely via the system-wide
configuration option:

:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset']`

Optionally it is possible to restrict this feature to non-admins only, by setting
the following system-wide option to "false".

:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins']`

Both options are available to be configured within the Maintenance Area
=> Settings module, or in the Install Tool, but can be set manually via
:file:`typo3conf/LocalConfiguration.php` or :file:`typo3conf/AdditionalConfiguration.php`.

In addition, it is possible for administrators to reset a users password.
This is especially useful for security purposes so an administrator does not
need to send a password over the wire in plaintext (e.g. email) to a user.

The administrator can use the CLI command:

.. code-block:: bash

   ./typo3/sysext/core/bin/typo3 backend:resetpassword https://www.example.com/typo3/ editor@example.com

where usage is described as this:

.. code-block:: bash

   backend:resetpassword <backendurl> <email>

Alternatively it is possible for administrators to use the "Backend users" module
and select the password reset button to initiate the password reset process for
a specific user.

Both options are only available for users that have an email address and a password
set.

Impact
======

Administrators do not have additional overhead to re-set passwords for editors,
and they do not need to add the passwords for editors themselves.

In addition, the email can be styled completely for HTML and plain-text only
versions through the Fluid-based templated email feature.

Further improvements on the horizon:

* Trigger a password-reset via CLI or the Backend users module
* Trigger a password-set email on creation of a new user, so the admin has no
  involvement in needing to know or share the password
* Require an email address when adding backend users to enable this feature for everybody
* Implement ways to allow the password reset functionality via different ways than email
* Find solutions for handling third-party authentication system

.. index:: LocalConfiguration, ext:backend
