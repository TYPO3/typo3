======================================================================================
Breaking: #63780 - Remove public properties words and word_strings from ReferenceIndex
======================================================================================

Description
===========

Public properties words and word_strings are removed from class \TYPO3\CMS\Core\Database\ReferenceIndex.
ReferenceIndex->words was always an empty array and ReferenceIndex->word_strings contained string from
input and text field of every record that was given to this class instance.


Impact
======

An extension relying on one of the public properties to be there will fail.


Affected installations
======================

It is unlikely that any extension used property words or word_strings. An instance could be
checked by searching for usages of class ReferenceIndex.


Migration
=========

The according logic needs to be re-implemented in the extension that used the content of these
properties.
