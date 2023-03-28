.. include:: /Includes.rst.txt

.. _deprecation-100014-1677078784:

==========================================================================================
Deprecation: #100014 - Function `getParameterFromUrl()` of `@typo3/backend/utility` module
==========================================================================================

See :issue:`100014`

Description
===========

The function :js:`getParameterFromUrl()` of the :js:`@typo3/backend/utility`
module was used to obtain a query string argument from an arbitrary URL.
Meanwhile, browsers received the `URLSearchParams API`_ that can be used
instead.

Therefore, :js:`getParameterFromUrl()` has been marked as deprecated.

Impact
======

Calling :js:`getParameterFromUrl()` will trigger a deprecation warning.


Affected installations
======================

All installations using third-party extensions relying on the deprecated code are
affected.


Migration
=========

Migrate to the following snippet to get the same result:

..  code-block:: javascript

    const paramValue = new URL(url, window.location.origin).searchParams.get(parameter);

.. _URLSearchParams API: https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams

.. index:: JavaScript, NotScanned, ext:backend
