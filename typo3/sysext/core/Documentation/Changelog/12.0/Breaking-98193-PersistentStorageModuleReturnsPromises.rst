.. include:: /Includes.rst.txt

.. _breaking-98193-1661262696:

=============================================================
Breaking: #98193 - Persistent storage module returns Promises
=============================================================

See :issue:`98193`

Description
===========

The methods of the JavaScript module :js:`@typo3/backend/storage/persistent` now
return native :js:`Promise` objects where jQuery-based promises were returned
previously.

This requires migration of any code using the returned jQuery promise.

This affects the following methods:

* :js:`set()`
* :js:`addToList()`
* :js:`unset()`

Impact
======

Using callbacks of jQuery-based promises (:js:`done`, :js:`fail` or :js:`always`)
will trigger JavaScript errors, as native :js:`Promise` objects don't know these
callbacks.

Affected installations
======================

All extensions using any of the aforementioned methods and relying on the
returned objects are affected.

Migration
=========

In most cases, changing the method name of the callback is sufficient, where the
following rules apply:

+-----------------------+-----------------+
| jQuery-based callback | Native callback |
+=======================+=================+
| done()                | then()          |
+-----------------------+-----------------+
| fail()                | catch()         |
+-----------------------+-----------------+
| always()              | finally()       |
+-----------------------+-----------------+

.. index:: Backend, JavaScript, NotScanned, ext:backend
