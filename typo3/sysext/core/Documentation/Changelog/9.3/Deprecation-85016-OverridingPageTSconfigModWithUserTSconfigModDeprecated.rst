.. include:: /Includes.rst.txt

===========================================================================
Deprecation: #84982 - Overriding page TSconfig mod. with user TSconfig mod.
===========================================================================

See :issue:`84982`

Description
===========

Overriding page TSconfig properties on a backend user or group basis is usually
done by prefixing the page TSconfig path with :typoscript:`page.` in user TSconfig.

As an exception, properties within the page TSconfig top level object :typoscript:`mod.` could
sometimes also be overridden in user TSconfig using :typoscript:`mod.` and omitting :typoscript:`page.`. This
has been deprecated: :typoscript:`mod.` now needs to be overridden in user TSconfig by prefixing
the path with :typoscript:`page.`, too.


Impact
======

User TSconfig paths that start with :typoscript:`mod.` will trigger a PHP :php:`E_USER_DEPRECATED` error and will
stop working with core v10.


Affected Installations
======================

Instances that set TSconfig on backend user or group basis starting with :typoscript:`mod.`.


Migration
=========

Simply prefix the user TSconfig path with :typoscript:`page.` as usual if overriding page TSconfig
on user TSconfig level. Example:

.. code-block:: typoscript

   // Before
   mod.web_list.disableSingleTableView = 1
   // After
   page.mod.web_list.disableSingleTableView = 1

.. index:: Backend, TSConfig, NotScanned
