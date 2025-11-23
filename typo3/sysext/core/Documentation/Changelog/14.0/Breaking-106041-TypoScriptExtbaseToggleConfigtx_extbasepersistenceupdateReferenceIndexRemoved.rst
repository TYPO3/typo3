..  include:: /Includes.rst.txt

..  _breaking-106041-1738329944:

========================================================================================================
Breaking: #106041 - TypoScript Extbase toggle config.tx_extbase.persistence.updateReferenceIndex removed
========================================================================================================

See :issue:`106041`

Description
===========

Extbase previously supported the TypoScript toggle
:typoscript:`config.tx_extbase.persistence.updateReferenceIndex` to control
whether the reference index should be updated when records are persisted.

It has become increasingly important that the reference index is always kept
up to date, since an increasing number of TYPO3 Core components rely on current
reference index data. Using the reference index at key points can improve read
and rendering performance significantly.

This toggle has been removed. Reference index updating is now always enabled.

Impact
======

The change may slightly increase database load, which can become noticeable
when Extbase updates many records at once.

Affected installations
======================

Instances with extensions that write many records using the Extbase persistence
layer may be affected.

Migration
=========

The TypoScript toggle
:typoscript:`config.tx_extbase.persistence.updateReferenceIndex` should be
removed from any extension codebase, as it is now ignored by Extbase.

..  index:: TypoScript, NotScanned, ext:extbase
