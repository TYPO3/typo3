.. include:: /Includes.rst.txt

.. _deprecation-87889-1705928143:

=================================================================
Deprecation: #87889 - TYPO3 backend entry point script deprecated
=================================================================

See :issue:`87889`

Description
===========

The TYPO3 backend entry point script `/typo3/index.php` is no longer needed and
deprecated in favor of handling all backend and frontend requests with `/index.php`.
It is still in place in case webserver configuration has not been adapted yet.

Note that the maintenance tool is still available via `/typo3/install.php`.


Impact
======

The TYPO3 backend route path is made configurable in order to protect against
application admin interface infrastructure enumeration (`WSTG-CONF-05`_).
Therefore, all requests are handled by the PHP script `/index.php` in order to
allow for variable admin interface URLs.
(via :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['entryPoint']`).


Affected installations
======================

All installations using the TYPO3 backend `/typo3`.


Migration
=========

There is a silent update in place which automatically updates the
webserver configuration file when accessing the install tool, at
least for Apache and Microsoft IIS webservers.

Note: This does not work if you are not using the default configuration,
which is shipped with Core and automatically applied during the TYPO3
installation process, as basis.

If you however use a custom web server configuration you may adapt as follows:


Apache configuration
--------------------

It is most important to rewrite all `typo3/*` requests to `/index.php`, but also
`RewriteCond %{REQUEST_FILENAME} !-d` should be removed in order for a request
to `/typo3/` to be directly served via `/index.php` instead of the deprecated
entry point `/typo3/index.php`.

Apache configuration before:

.. code-block:: apache
   :emphasize-lines: 2-4

   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteCond %{REQUEST_FILENAME} !-l
   RewriteRule ^typo3/(.*)$ %{ENV:CWD}typo3/index.php [QSA,L]

   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteCond %{REQUEST_FILENAME} !-l
   RewriteRule ^.*$ %{ENV:CWD}index.php [QSA,L]


Apache configuration after:

.. code-block:: apache
   :emphasize-lines: 2

   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteRule ^typo3/(.*)$ %{ENV:CWD}index.php [QSA,L]

   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteCond %{REQUEST_FILENAME} !-l
   RewriteRule ^.*$ %{ENV:CWD}index.php [QSA,L]


Nginx configuration
-------------------

Nginx configuration before:

.. code-block:: nginx
   :emphasize-lines: 3

    location /typo3/ {
        absolute_redirect off;
        try_files $uri /typo3/index.php$is_args$args;
    }

Nginx configuration after:

.. code-block:: nginx
   :emphasize-lines: 3

    location /typo3/ {
        absolute_redirect off;
        try_files $uri /index.php$is_args$args;
    }


Related
=======

- :ref:`feature-87889-1705931337`

.. _WSTG-CONF-05: https://owasp.org/www-project-web-security-testing-guide/v42/4-Web_Application_Security_Testing/02-Configuration_and_Deployment_Management_Testing/05-Enumerate_Infrastructure_and_Application_Admin_Interfaces

.. index:: Backend, NotScanned, ext:backend
