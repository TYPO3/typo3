.. include:: /Includes.rst.txt

.. _deprecation-99523-1673454068:

========================================================
Deprecation: #99523 - Deprecate type="none" pass_content
========================================================

See :issue:`99523`

Description
===========

The TCA option :php:`pass_content` for :php:`type="none"` fields has been
marked deprecated in TYPO3 v12 and will be removed with v13.

Impact
======

Using the option should be avoided and has no impact anymore.

Instances with field configurations, section `config` of :php:`type="none"`
having key :php:`pass_content` will trigger a deprecation warning during
TCA cache warmup.


Affected installations
======================

The :php:`type="none"` TCA field is a rarely used type, its main purpose is
to allow virtual fields (a field without corresponding database column).

Instances are affected when the backend "lowlevel" search
in :php:`$GLOBALS['TCA']` for "pass_content" reveals matches.

Migration
=========

The :php:`pass_content=true` option was documented to not :php:`htmlspecialchars()`
the value. This is an edge case anyways, since the :php:`type="none"` is designed
to not have a database field at all, so there is usually no value. Additionally,
the current behavior still applies :php:`htmlspecialchars()` to the value. This has
not been fixed in TYPO3 v11 and v12 since it may open a security issue with existing
instances.

Instances that need non-HTML escaped output with :php:`type="none"` should register an
own :php:`renderType` element for the field, as documented in
the :ref:`TYPO3 explained FormEngine chapter<t3coreapi:FormEngine-Rendering-NodeFactory>`.

.. index:: Backend, TCA, NotScanned, ext:backend
