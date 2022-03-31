.. include:: /Includes.rst.txt

===============================================================
Breaking: #92693 - Remove LinkHandler Linktype in Linkvalidator
===============================================================

See :issue:`92693`

Description
===========

Linkvalidator ships with several link type classes that are used to check
specific links such as ExternalLinktype, Filelinktype etc.

The link type LinkHandler is no longer used by default (see Page TSconfig
:typoscript:`mod.linkvalidator.linktypes`). It was used to check links of the extension
"linkhandler" which is now outdated. The latest version supports TYPO3 4.1.0.

LinkHandler functionality was integrated into the core in TYPO3 8, but the
format of the links has changed since then.

The LinkHandler link type expects links which start with "record:" -
a syntax that is now outdated.

Links to records are successfully checked in the InternalLinktype class.

Impact
======

It is no longer possible to use the "linkhandler" link type. Setting this
in the configuration will not have any effect.


Affected Installations
======================

There should be no affected installations as the linkhandler extension and
the corresponding format of the links has long been outdated.

Migration
=========

Normally, no migration is necessary.

You should remove the linkhandler link type from the page TSconfig configuration:

.. code-block:: diff

   - :typoscript:`mod.linkvalidator.linktypes = db,file,external,linkhandler`
   + :typoscript:`mod.linkvalidator.linktypes = db,file,external`

You should no longer use :typoscript:`linkhandler.reportHiddenRecords = 0`.

.. code-block:: diff

   - :typoscript:`mod.linkvalidator.linkhandler.reportHiddenRecords = 0`

.. index:: Backend, NotScanned, ext:linkvalidator
