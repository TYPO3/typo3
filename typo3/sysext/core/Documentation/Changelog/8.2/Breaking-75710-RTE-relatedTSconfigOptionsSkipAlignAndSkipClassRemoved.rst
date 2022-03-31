
.. include:: /Includes.rst.txt

===============================================================================
Breaking: #75710 - RTE-related TSconfig options skipAlign and skipClass removed
===============================================================================

See :issue:`75710`

Description
===========

The two RTE-related TSconfig options :typoscript:`RTE.default.proc.skipAlign` and :typoscript:`RTE.default.proc.skipClass`
that don't allow align and class attributes when converting content from the RTE to the database have been removed.


Impact
======

Setting these options will have no effect anymore.


Affected Installations
======================

Any installation setting custom TSconfig options for :typoscript:`skipAlign`, :typoscript:`skipClass` or setting
:typoscript:`keepPDIVattribs` without :typoscript:`class` and :typoscript:`align` as values are affected.


Migration
=========

In order to allow class and align attributes in paragraph tags, the option :typoscript:`keepPDIVattribs`
needs to be extended to also include "class" and "align" as values, which is done by default.

If an installation has custom TSconfig settings using :typoscript:`keepPDIVattribs`, the two attributes need to be added
accordingly to still allow class and align attributes to be kept when transforming from the RTE to the database.

.. index:: TSConfig, RTE, Backend
