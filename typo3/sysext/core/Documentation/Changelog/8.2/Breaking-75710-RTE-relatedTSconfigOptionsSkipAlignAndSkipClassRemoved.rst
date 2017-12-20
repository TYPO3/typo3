
.. include:: ../../Includes.txt

===============================================================================
Breaking: #75710 - RTE-related TSconfig options skipAlign and skipClass removed
===============================================================================

See :issue:`75710`

Description
===========

The two RTE-related TSconfig options :ts:`RTE.default.proc.skipAlign` and :ts:`RTE.default.proc.skipClass`
that don't allow align and class attributes when converting content from the RTE to the database have been removed.


Impact
======

Setting these options will have no effect anymore.


Affected Installations
======================

Any installation setting custom TSconfig options for :ts:`skipAlign`, :ts:`skipClass` or setting
:ts:`keepPDIVattribs` without :ts:`class` and :ts:`align` as values are affected.


Migration
=========

In order to allow class and align attributes in paragraph tags, the option :ts:`keepPDIVattribs`
needs to be extended to also include "class" and "align" as values, which is done by default.

If an installation has custom TSconfig settings using :ts:`keepPDIVattribs`, the two attributes need to be added
accordingly to still allow class and align attributes to be kept when transforming from the RTE to the database.

.. index:: TSConfig, RTE, Backend
