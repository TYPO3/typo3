.. include:: /Includes.rst.txt

.. _deprecation-98093-1681741493:

================================================================
Deprecation: #98093 - ext_icon.* as extension icon file location
================================================================

See :issue:`98093`

Description
===========

Since :issue:`#77349` it is possible to place the extension icon, which is
displayed at various places in the backend, e.g. in the extension manager, in
an extensions' `Resources/Public/Icons/` directory. The `Resources/` directory
is :ref:`by convention <t3coreapi:extension-files-locations>` the place to
store such files. To simplify extension registration and to fully follow the
convention have the following file locations been deprecated:

* :file:`ext_icon.png`
* :file:`ext_icon.svg`
* :file:`ext_icon.gif`

Impact
======

Adding an extension icon using one of the mentioned file locations will raise
a deprecation level log message and will stop working with TYPO3 v13.


Affected installations
======================

TYPO3 installations with custom extensions using the deprecated file locations.


Migration
=========

Place your extension icon as `Extension.*` into `Resources/Public/Icons/`,
as described in :ref:`Feature: #77349 - Additional locations for extension icons <feature-77349>`.

.. index:: Backend, NotScanned, ext:core
