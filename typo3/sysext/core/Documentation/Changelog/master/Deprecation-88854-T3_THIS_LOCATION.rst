.. include:: ../../Includes.txt

======================================
Deprecation: #88854 - T3_THIS_LOCATION
======================================

See :issue:`88854`

Description
===========

The global JavaScript variable :js:`T3_THIS_LOCATION` containing the URL to the current document (if not modified) has
been marked as deprecated.


Impact
======

Since this is a global JavaScript variable, no proper deprecation layer applies and thus no deprecation notice is rendered.

Some PHP API uses :js:`T3_THIS_LOCATION` (e.g. :php:`BackendUtility::getLinkToDataHandlerAction()` with second argument
being `-1`) has been marked as deprecated as well.


Affected Installations
======================

All third party extensions using :js:`T3_THIS_LOCATION` are affected.


Migration
=========

When generating URLs containing a `returnUrl` (a common use-case for :js:`T3_THIS_LOCATION`), consider using either
:php:`rawurldecode(GeneralUtility::getIndpEnv('REQUEST_URI'))` or :php:`normalizedParams` in the PSR-7 ServerRequest object:
:php:`$request->getAttribute('normalizedParams')->getRequestUri()`.

In general, `onclick` handlers doing a redirect are considered bad practice. Use HTML's `href` attribute and attach
custom click handlers, if necessary.

.. index:: Backend, JavaScript, PHP-API, NotScanned, ext:backend
