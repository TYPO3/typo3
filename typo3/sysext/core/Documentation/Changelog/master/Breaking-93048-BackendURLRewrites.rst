.. include:: ../../Includes.txt

.. _changelog-Breaking-93048-BackendURLRewrites:

=======================================
Breaking: #93048 - Backend URL rewrites
=======================================

See :issue:`93048`

Description
===========

To introduce speaking urls to the TYPO3 backend, a new rewrite
rule for the backend is necessary. Therefore the rewrite process
should not longer be stopped if the  `typo3/` directory is accessed,
like it was configured for a long time. Instead, all requests below
`/typo3/` which do not exist, are now redirected to the TYPO3 Backend
entry point.

Further do the Backend URLs now not longer require the `&route=`
parameter since its value is now part of the URL. For example the
main entry point changed from `/typo3/index.php?route=%2Fmain` to
`/typo3/main`.

The `&route=` parameter will however be still applied to the URL
for backwards compatibility.


Impact
======

Accessing the backend without changing the webserver configuration
will usually lead to a `404 - Not found` response.

Custom backend links which are not build using the :php:`UriBuilder`
API also may lead to a `404 - Not found` response.

Using relative paths for backend links, e.g. for icons / images, will
may not longer work as expected.

Extensions relying on the `&route=` parameter to be set will still work
but break at least in v12 when this parameter will finally be removed.


Affected Installations
======================

All installations are affected.


Migration
=========

There is a silent update in place which automatically updates the
webserver configuration file when accessing the install tool, at
least for Apache and Microsoft IIS webservers.

Note: This does not work if you are not using the default configuration,
which is shipped with Core and automatically applied during the TYPO3
installation process, as basis. No worries, some custom adjustments like
redirects do not prevent the update. Only the default rewrite rules must
be in place.

If you howver use a fully custom configuration, especially when using
a custom entry point for the backend, you may have to perform the
necessary changes manually. Therefore, please have a look at the changes
to the default `.htaccess` configuration, as example.

Before:

.. code-block:: none

   RewriteRule ^(?:typo3/|fileadmin/|typo3conf/|typo3temp/|uploads/) - [L]

After:

.. code-block:: none

   RewriteRule ^(?:fileadmin/|typo3conf/|typo3temp/|uploads/) - [L]

   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteCond %{REQUEST_FILENAME} !-l
   RewriteCond %{REQUEST_URI} ^/typo3/.*$
   RewriteRule ^typo3/(.*)$ %{ENV:CWD}typo3/index.php [QSA,L]

Additionally, make sure to use the public :php:`UriBuilder` API for
all custom generated backend links.

Finally, check custom backend modules for the use of relative paths,
because they may not longer work as expected.


Related
=======

- :ref:`changelog-Feature-93048-IntroduceBackendURLRewrites`

.. index:: Backend, NotScanned, ext:backend
