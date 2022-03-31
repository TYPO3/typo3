.. include:: /Includes.rst.txt

=====================================================
Breaking: #79273 - Removed RteHtmlParser proc options
=====================================================

See :issue:`79273`

Description
===========

The following TSconfig options for processing content of RTE fields have been removed:

* :typoscript:`RTE.default.proc.dontConvBRtoParagraph`
* :typoscript:`RTE.default.proc.dontProtectUnknownTags_rte`
* :typoscript:`RTE.default.proc.dontConvAmpInNBSP_rte`


Impact
======

Setting any of these options has no effect anymore.

Content coming from the database towards the RTE will now always keep unknown tags (but HSC'ed), and never have any
double-encoded :html:`&nbsp;` characters - this was a default since a decade already.

Content stored in the database will now always treat :html:`<br>` tags as intentional and not treat them like paragraphs, a behaviour which
is common in modern Rich Text Editors.


Affected Installations
======================

Installations explicitly setting :typoscript:`RTE.default.proc.dontConvBRtoParagraph = 0`, :typoscript:`RTE.default.proc.dontProtectUnknownTags_rte = 1` or
:typoscript:`RTE.default.proc.dontConvAmpInNBSP_rte = 1` might experience different results when editing and saving content via an RTE.


Migration
=========

Remove the TSconfig options, as they have no effect anymore. Any custom implementation which is necessary should be handled
via separate entryHtmlParser and exitHtmlParsers in both directions.

.. index:: RTE, TSConfig
