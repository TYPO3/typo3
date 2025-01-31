..  include:: /Includes.rst.txt

..  _breaking-106041-1738329944:

========================================================================================================
Breaking: #106041 - TypoScript Extbase toggle config.tx_extbase.persistence.updateReferenceIndex removed
========================================================================================================

See :issue:`106041`

Description
===========

Extbase had the TypoScript toggle :typoscript:`config.tx_extbase.persistence.updateReferenceIndex`
whether the reference index shall be updated when records are persisted.

It becomes more and more important the reference index is always up to date since
an increasing number of core code relies on current reference index data. Using
the reference index at key places can improve read and rendering performance
significantly.

This toggle has been removed, reference index updating is now always enabled.


Impact
======

The change may increase database load slightly which may become noticeable when
Extbase changes many records at once.


Affected installations
======================

Instances with extensions writing many records using the Extbase persistence layer
may be affected.


Migration
=========

TypoScript toggle :typoscript:`config.tx_extbase.persistence.updateReferenceIndex`
should be removed from any extensions codebase, it is ignored by Extbase.


..  index:: TypoScript, NotScanned, ext:extbase
