.. include:: ../../Includes.txt

======================================
Feature: #75806 - Add hreflang support
======================================

See :issue:`75806`

Description
===========

"hreflang" tags are now added automatically for multilanguage websites based on the one-tree principle.

The href is relative as long as the domain is the same. If the domain differs the href becomes absolute.
The x-default href is the first supported language.
The value of "hreflang" is the one set in the new sites module.

.. index:: Frontend, ext:seo
