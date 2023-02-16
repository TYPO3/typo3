.. include:: /Includes.rst.txt

.. _deprecation-100047-1677607925:

==========================================================
Deprecation: #100047 - Deprecated ConditionMatcher classes
==========================================================

See :issue:`100047`

Description
===========

The following classes have been marked as deprecated in TYPO3 v12 and will
be removed with v13:

* :php:`\TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\ConditionMatcherInterface`
* :php:`\TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher`
* :php:`\TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher`
* :php:`\TYPO3\CMS\Frontend\Configuration\TypoScript\ConditionMatching\ConditionMatcher`


Impact
======

The TYPO3 core only uses these classes within the old TypoScript parser classes,
which have been deprecated as well. Using the classes will trigger a deprecation
level log entry.


Affected installations
======================

There was most likely little need do implement own variants of the above classes,
the underlying :php:`ExpressionLanguage` construct has an own API to add own
variables and functions for this TypoScript condition related symfony expression
language usage.


Migration
=========

No direct migration possible. These classes have been merged into the new
TypoScript parser approach, specifically to class
:php:`\TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeConditionMatcherVisitor`.

Adding TypoScript related expression language variables and functions should be
done using :php:`\TYPO3\CMS\Core\ExpressionLanguage\ProviderInterface`.

.. index:: PHP-API, FullyScanned, ext:core
