==========================================================
Deprecation: #65465 - Deprecate errorLog in ReferenceIndex
==========================================================

Description
===========

The function ``\TYPO3\CMS\Core\Database\ReferenceIndex::error()`` and the according property
``\TYPO3\CMS\Core\Database\ReferenceIndex::errorLog`` have been deprecated. It was not used and always empty.


Impact
======

Calling ReferenceIndex::error() will throw a deprecation message. This function should not be used from outside the
core.


Migration
=========

Extensions that used this property to log errors have to use their own errorLog.