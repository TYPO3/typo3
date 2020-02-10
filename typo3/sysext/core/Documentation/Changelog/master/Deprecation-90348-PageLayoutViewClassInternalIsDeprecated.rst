.. include:: ../../Includes.txt

===================================================================
Deprecation: #90348 - PageLayoutView class (internal) is deprecated
===================================================================

See :issue:`90348`

Description
===========

The :php:`PageLayoutView` class, which is considered internal API, has been deprecated in favor of the new Fluid-based alternative which renders the "page" BE module.


Impact
======

Implementations which depend on :php:`PageLayoutView` should prepare to use the alternative implementation (by overlaying and overriding Fluid templates of EXT:backend).


Affected Installations
======================

Any site which uses PSR-14 events or backend content rendering hooks associated with :php:`PageLayoutView` such as :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawFooter']`.


Migration
=========

Fluid templates can be extended or replaced to render custom header, footer or preview of a given :php:`CType`, see feature description for feature 90348.

.. index:: Backend, Fluid, NotScanned, ext:backend
