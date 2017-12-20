
.. include:: ../../Includes.txt

========================================================================
Deprecation: #65956 - $returnHTML parameter of DebugUtility::debugRows()
========================================================================

See :issue:`65956`

Description
===========

The parameter `$returnHTML` of the method `\TYPO3\CMS\Core\Utility\DebugUtility::debugRows()` is not used anymore and
has been marked for deprecation.


Impact
======

The parameter is not used anymore.


Affected installations
======================

All method calls using this parameter are affected.


Migration
=========

Remove the parameter in the method call.


.. index:: PHP-API
