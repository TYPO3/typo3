.. include:: ../../Includes.txt

===========================================================================
Deprecation: #84982 - Overriding page TSconfig mod. with user TSconfig mod.
===========================================================================

See :issue:`84982`

Description
===========

Overriding page TSconfig properties on a backend user or group basis is usually
done by prefixing the page TSconfig path with :ts:`page.` in user TSconfig.

As an exception, properties within the page TSconfig top level object :ts:`mod.` could
sometimes also be overriden in user TSconfig using :ts:`mod.` and omitting :ts:`page.`. This
has been deprecated: :ts:`mod.` now needs to be overriden in user TSconfig by prefixing
the path with :ts:`page.`, too.


Impact
======

User TSconfig paths that start with :ts:`mod.` will trigger a PHP :php:`E_USER_DEPRECATED` error and will
stop working with core v10.


Affected Installations
======================

Instances that set TSconfig on backend user or group basis starting with :ts:`mod.`.


Migration
=========

Simply prefix the user TSconfig path with :ts:`page.` as usual if overriding page TSconfig
on user TSconfig level. Example:

.. code-block:: typoscript

    // Before
    mod.web_list.disableSingleTableView = 1
    // After
    page.mod.web_list.disableSingleTableView = 1

.. index:: Backend, TSConfig, NotScanned