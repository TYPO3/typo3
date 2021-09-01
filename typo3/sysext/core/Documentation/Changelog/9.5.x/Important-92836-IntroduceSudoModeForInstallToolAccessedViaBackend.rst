.. include:: ../../Includes.txt

=============================================================================
Important: #92836 - Introduce sudo mode for Install Tool accessed via backend
=============================================================================

See :issue:`92836`

Description
===========

When accessing the Install Tool via backend user interface, currently logged in
backend users have to confirm their user password again in order to get access
to the Install Tool. As an alternative, it is also possible to use the install
tool password (reasons described below in "side effects" section). This is done
in order to mitigate unintended modifications that might occur as result
of e.g. possible cross-site scripting vulnerabilities in the system.

Standalone Install Tool is not affected by sudo mode confirmation.
This change enforces mitigation as mentioned in TYPO3-CORE-SA-2020-006_.


Potential side effects
======================

Albeit default local authentication mechanisms are working well, there are
side effects for 3rd party extensions that make use of these `auth` service
chains as well - such as multi-factor authentication or single sign-on handling.

As an alternative, it is possible to confirm actions using the Install Tool
password, instead of confirming with users' password (which might be handled
with separate remote services).

Services that extend authentication with custom additional factors (2FA/MFA)
are advised to intercept only valid login requests instead of all `authUser`
invocations.

.. code-block:: php

   class MyAuthenticationService
   extends \TYPO3\CMS\Core\Authentication\AbstractAuthenticationService
   {
       public function authUser(array $user)
       {
           // only handle actual login requests
           if (empty($this->login['status'])
               || $this->login['status'] !== 'login') {
               // skip this service, hand over to next in chain
               return 100;
           }
           ...
           // usual processing for valid login requests
           ...
       }
   }

Please see this pull-request_ for a 2FA/MFA extension as an example.


.. _TYPO3-CORE-SA-2020-006: https://typo3.org/security/advisory/typo3-core-sa-2020-006
.. _pull-request: https://github.com/derhansen/sf_yubikey/pull/45/files
.. index:: Backend, ext:install
