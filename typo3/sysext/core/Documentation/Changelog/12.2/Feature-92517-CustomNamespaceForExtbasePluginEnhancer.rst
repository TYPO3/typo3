.. include:: /Includes.rst.txt

.. _feature-92517-1671616097:

==============================================================
Feature: #92517 - Custom namespace for Extbase plugin enhancer
==============================================================

See :issue:`92517`

Description
===========

The Extbase plugin enhancer for frontend routing allows to either set `extension`
and `plugin` OR to set `namespace`. If `extension` and `plugin` were given,
those were used.

However, the `namespace` option is automatically constituted by the extension and
plugin options if it was not set intentionally. It is mainly used when overriding
the custom extension and plugin options with a custom (usually shortened) namespace,
so that the `namespace` is now always respected and preferred if all three options
are set.

Impact
======

If all of `namespace` and `extension` and `plugin` options are configured,
the namespace option is now preferred within the Extbase plugin enhancer.

.. index:: Frontend, ext:frontend
