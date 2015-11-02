=================================================================
Deprecation: #71153 - Several DocumentTemplate methods deprecated
=================================================================

Description
===========

The following methods from ``TYPO3\CMS\Backend\Template\DocumentTemplate`` has been deprecated.

``section``
``divider``
``sectionHeader``
``sectionBegin``
``sectionEnd``


Affected Installations
======================

Instances with custom backend modules that use one of the aforementioned methods.


Migration
=========

Use plain HTML instead.
