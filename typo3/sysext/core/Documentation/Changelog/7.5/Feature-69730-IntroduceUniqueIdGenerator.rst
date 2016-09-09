
.. include:: ../../Includes.txt

==============================================
Feature: #69730 - Introduce uniqueId generator
==============================================

See :issue:`69730`

Description
===========

A new method `getUniqueId()` has been added to the StringUtility class.
Now there is a common way to generate an unique identifier which can be
used even in HTML tag attributes as it removes the invalid dot character.


.. code-block:: php

	$uniqueId = \TYPO3\CMS\Core\Utility\StringUtility::getUniqueId('Prefix');


Impact
======

No need to remove the dot manually anymore.
