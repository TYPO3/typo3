.. include:: ../../Includes.txt

=====================================================
Important: #88824 - Add cache for error page handling
=====================================================

See :issue:`88824`

Description
===========

In order to prevent possible DoS attacks when the page-based error handler
is used, the content of the 404 error page is now cached in the TYPO3
page cache. Any dynamic content on the error page (e.g. content created
by TypoScript or uncached plugins) will therefore also be cached.

If the 404 error page contains dynamic content, TYPO3 administrators must
ensure that no sensitive data (e.g. username of logged in frontend user)
will be shown on the error page.

If dynamic content is required on the 404 error page, it is recommended
to implement a custom PHP based error handler.

.. index:: Backend, ext:backend
