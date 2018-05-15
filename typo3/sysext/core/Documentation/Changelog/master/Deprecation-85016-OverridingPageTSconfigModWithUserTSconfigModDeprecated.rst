.. include:: ../../Includes.txt

======================================================================================
Deprecation: #84982 - Overriding page TSconfig mod. with user TSconfig mod. deprecated
======================================================================================

See :issue:`84982`

Description
===========

Overriding page TSconfig properties on a backend user or group basis is usually
done by prefixing the page TSconfig path with `page.` in user TSconfig.

As an exception, properties within the page TSconfig top level object `mod.` could
sometimes also be overriden in user TSconfig using `mod.` and omitting `page.`. This
has been deprecated: `mod.` now needs to be overriden in user TSconfig by prefixing
the path with `page.`, too.


Impact
======

User TSconfig paths that start with `mod.` log a deprecation message and will
stop working with core v10.


Affected Installations
======================

Instances that set TSconfig on backend user or group basis starting with `mod.`.


Migration
=========

Simply prefix the user TSconfig path with `page.` as usual if overriding page TSconfig
on user TSconfig level. Example:

.. code-block:: typoscript

    // Before
    mod.web_list.disableSingleTableView = 1
    // After
    page.mod.web_list.disableSingleTableView = 1

.. index:: Backend, TSConfig, NotScanned