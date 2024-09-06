.. include:: /Includes.rst.txt

.. _feature-87889-1705931337:

================================================
Feature: #87889 - Configurable TYPO3 backend URL
================================================

See :issue:`87889`

Description
===========


The TYPO3 backend URL is made configurable in order to enable optional
protection against application admin interface infrastructure
enumeration (`WSTG-CONF-05`_). Both, frontend and backend requests are
now handled by the PHP script :file:`/index.php` to enable virtual admin
interface URLs.

The default TYPO3 backend entry point path `/typo3` can be changed by
specifying a custom URL path or domain name in
:php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['entryPoint']`.

This change requires web server adaption. A silent migration and
according documentation for custom web server configurations is added.
A deprecation layer (for non-adapted systems) is in place that rewrites
the server environment variables passed to :file:`/typo3/index.php` as if
:file:`/index.php` was used directly. This layer will be removed in TYPO3 v14.

This change does not take assets into account, only routing is adapted.
That means Composer mode will use assets provided via `/_assets` as before
and TYPO3 classic mode will serve backend assets from `/typo3/*` even if
another backend URL is used and configured.


Configure to a specific path
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['BE']['entryPoint'] = '/admin';

Now point your browser to `https://example.com/admin` to log into the TYPO3
backend.


Configure to use a distinct (sub)domain
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['BE']['entryPoint'] = 'https://backend.example.com';
   $GLOBALS['TYPO3_CONF_VARS']['SYS']['cookieDomain'] = '.example.com';


Now point your browser to `https://backend.example.com/` to log into the TYPO3
backend.

Legacy-Free installation
~~~~~~~~~~~~~~~~~~~~~~~~~

The legacy entry point :file:`/typo3/index.php` is no longer needed and deprecated in
favor of handling all backend and frontend requests with :file:`/index.php`. The
entry point is still in place, in case webserver configuration has not been adapted
yet. The maintenance and emergency tool is still available via
:file:`/typo3/install.php` in order to work in edge cases like broken web server
routing.

In Composer mode there is an additional opt-out for the installation of the
legacy entrypoint that can be defined in your project's :file:`composer.json`
file:

.. code-block:: json

   "extra": {
     "typo3/cms": {
       "install-deprecated-typo3-index-php": false
     }
   }


Impact
======

The TYPO3 backend route path is made configurable in order to protected against
application admin interface infrastructure enumeration (`WSTG-CONF-05`_).
Therefore, all requests are handled by the PHP script :file:`/index.php` in order to
allow for variable admin interface URLs.

.. _WSTG-CONF-05: https://owasp.org/www-project-web-security-testing-guide/v42/4-Web_Application_Security_Testing/02-Configuration_and_Deployment_Management_Testing/05-Enumerate_Infrastructure_and_Application_Admin_Interfaces

.. index:: Backend, ext:backend
