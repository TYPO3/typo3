.. include:: /Includes.rst.txt

.. _feature-92517-1671616097:

============================================================
Feature: #92517 - Custom Namespace for ExtbasePluginEnhancer
============================================================

See :issue:`92517`

Description
===========

The Extbase Plugin Enhancer for Frontend Routing allows to either set `extension`
and `plugin` OR to set `namespace`. If `extension` and `plugin` were given,
those were used.

However, the `namespace` option is automatically constituted by the extension and
plugin options, if not set intentionally, and is mainly used when overriding
the custom extension and plugin options by a custom (usually shortened) namespace,
so the `namespace` is now always respected and preferred if all three options
are set.

Impact
======

If all of `namespace` and `extension` and `plugin` options are configured,
the namespace option is now preferred within the Extbase Plugin Enhancer.

.. index:: Frontend, ext:frontend
