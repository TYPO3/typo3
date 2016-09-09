
.. include:: ../../Includes.txt

======================================================================================
Breaking: #63780 - Remove public properties words and word_strings from ReferenceIndex
======================================================================================

See :issue:`63780`

Description
===========

Public properties `words` and `word_strings` have been removed from class `\TYPO3\CMS\Core\Database\ReferenceIndex`.
`ReferenceIndex->words` was always an empty array and `ReferenceIndex->word_strings` contained strings from
input- and text fields of every record that was given to this class instance.


Impact
======

An extension relying on one of these public properties will fail.


Affected installations
======================

It is unlikely that any extension used the properties words or word_strings. An instance could be
checked by searching for usages of class `ReferenceIndex`.


Migration
=========

The according logic needs to be re-implemented in an extension that used the content of these
properties.
