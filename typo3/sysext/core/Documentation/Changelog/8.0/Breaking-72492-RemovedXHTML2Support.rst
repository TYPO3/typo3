
.. include:: ../../Includes.txt

=========================================
Breaking: #72492 - Removed XHTML2 support
=========================================

See :issue:`72492`

Description
===========

The support for XHTML2 documents in the TYPO3 Frontend has been removed.


Impact
======

The TypoScript option `config.doctype = xhtml_2` has no effect anymore.


Affected Installations
======================

Any TYPO3 instance using XHTML2 for frontend rendering.


Migration
=========

Disable all header code and set your own doctype.

.. index:: Frontend, TypoScript
