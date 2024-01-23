.. include:: /Includes.rst.txt

.. _breaking-102260-1698252706:

======================================================
Breaking: #102260 - Removed TCA ['softref'] = 'notify'
======================================================

See :issue:`102260`

Description
===========

:php:`TCA` columns type fields like :php:`input` and :php:`text` obey the :php:`config` key
:php:`softref`. One of the allowed soft reference parsers is :php:`notify` implemented
by class :php:`\TYPO3\CMS\Core\DataHandling\SoftReference\NotifySoftReferenceParser`.

This soft reference parser fits no apparent use case and has been removed.


Impact
======

Involving the :php:`notify` key in the comma-separated list of TCA columns config
:php:`softref` or a flex form data structure column definition does not trigger
any action anymore and may log a warning this parser hasn't been found.


Affected installations
======================

There was little reason to activate this soft reference parser in the first place
since it essentially did nothing. Instances with extensions having TCA column config
:php:`softref` set to a value including :php:`notify` will be affected. That's a very
rare use case. The extension scanner will not notify about this, but the
:php:`SoftReferenceParserFactory` will add a log entry this parser was not found upon
using an affected record.


Migration
=========

Remove key :php:`notify` from TCA columns :php:`softref` list.


.. index:: TCA, NotScanned, ext:core
