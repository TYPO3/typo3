
.. include:: /Includes.rst.txt

===========================================================================
Breaking: #72830 - Removed deprecated RTE transformations ts & ts_transform
===========================================================================

See :issue:`72830`

Description
===========

The transformation option "ts" and "ts_transform" are outdated since the
introduction of CSS Styled Content and have been removed from the TYPO3 Core.

The two related PHP methods `TS_transform_rte` and `TS_transform_db` had the
second method parameter dropped.


Impact
======

Setting the TSconfig option `RTE.proc.overruleMethod = ts` or `RTE.proc.overruleMethod = ts_transform` will result
in not having any transformation applied to the content anymore.


Affected Installations
======================

Any installation with custom RTE transformation options to render legacy HTML code.


Migration
=========

Use `ts_css` instead, which is set by default since TYPO3 4.0.

.. index:: TSConfig, Frontend, RTE
