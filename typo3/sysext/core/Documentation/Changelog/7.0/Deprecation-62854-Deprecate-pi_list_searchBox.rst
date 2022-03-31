
.. include:: /Includes.rst.txt

=========================================================
Deprecation: #62854 - Abstractplugin->pi_list_searchBox()
=========================================================

See :issue:`62854`

Description
===========

Method :code:`pi_list_searchBox()` of AbstractPlugin (aka pibase) was used with very old
search solutions and is hopelessly outdated. It is now discouraged to be used
and will be removed with next major version.


Impact
======

Extensions still using :code:`pi_list_searchBox()` will throw a deprecation warning.

Affected installations
======================

Any extension still using this method needs to be adapted.


.. index:: PHP-API, Frontend
