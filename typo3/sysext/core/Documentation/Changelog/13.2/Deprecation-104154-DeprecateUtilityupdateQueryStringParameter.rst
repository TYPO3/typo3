.. include:: /Includes.rst.txt

.. _deprecation-104154-1718802119:

=====================================================================
Deprecation: #104154 - Deprecate Utility.updateQueryStringParameter()
=====================================================================

See :issue:`104154`

Description
===========

The :js:`Utility.updateQueryStringParameter()` method in the
:js:`@typo3/backend/utility.js` module was introduced in TYPO3 v8 as a bugfix
for highlighting in the old ExtJS-based page tree. Since removal of ExtJS in
TYPO3 v9 the method has been unused.

Because safe removal of the method cannot be guaranteed as this point, it is
therefore deprecated.


Impact
======

Calling :js:`Utility.updateQueryStringParameter()` will result in a JavaScript
warning.


Affected installations
======================

All 3rd party extensions using the deprecated method.


Migration
=========

Now, JavaScript supports the :js:`URL` and its related :js:`URLSearchParams`
object that can be used to achieve the same result:

..  code-block:: javascript

    const url = new URL('http://localhost?baz=baz');
    url.searchParams.set('baz', 'bencer');
    const urlString = url.toString(); // http://localhost?baz=bencer

.. index:: JavaScript, NotScanned, ext:backend
