.. include:: /Includes.rst.txt

.. _breaking-96263:

==================================================================
Breaking: #96263 - Remove jQuery promise support for AJAX requests
==================================================================

See :issue:`96263`

Description
===========

With :issue:`89738`, a polyfill for jQuery promises was introduced to ease the
migration of :js:`$.ajax()` to our AJAX request API.

The polyfilled methods :js:`done()` and :js:`fail()` are now removed.

Impact
======

Relying on the existence of the polyfill will trigger JavaScript errors.

Affected Installations
======================

All extensions using the polyfilled methods are affected.

Migration
=========

For success handling, replace :js:`done()` with :js:`then()`.

Example:

..  code-block:: js

    // Polyfill
    new AjaxRequest('/foobar/baz').get().done(function(response) {
      // do stuff
    });

    // Native
    new AjaxRequest('/foobar/baz').get().then(async function(response) {
      // do stuff
    });

For error handling, replace :js:`fail()` with :js:`catch()`.

Example:

..  code-block:: js

    // Polyfill
    new AjaxRequest('/foobar/baz').get().fail(function() {
      // oh noes
    });

    // Native
    new AjaxRequest('/foobar/baz').get().catch(function() {
      // oh noes
    });

.. index:: JavaScript, NotScanned, ext:core
