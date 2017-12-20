.. include:: ../../Includes.txt

=============================================================
Breaking: #78002 - Enforce cHash argument for Extbase actions
=============================================================

See :issue:`78002`

Description
===========

URIs to Extbase actions now need a valid cHash per default. This is required for
both cached and uncached actions. The behavior can be disabled for all actions
using the feature switch `requireCHashArgumentForActionArguments`.


Impact
======

All generated links to Extbase actions without having a valid cHash will fail.


Affected Installations
======================

All generated links to Extbase actions that explicitly disabled the cHash are
affected - like `<f:link.action action="..." noCacheHash="1"/>`


Migration
=========

Either one of the following:

+ ensure to use a valid cHash, e.g. by removing the
  `noCacheHash="1"` argument from link view-helpers
+ disable the `feature.requireCHashArgumentForActionArguments`
  setting for the particular extension

.. index:: Frontend, PHP-API, ext:extbase