..  include:: /Includes.rst.txt

..  _breaking-101392-1742741515:

=================================================================================
Breaking: #101392 - getIdentifier() and setIdentifier() from AbstractFile removed
=================================================================================

See :issue:`101392`

Description
===========

When using the PHP API of `File Abstraction Layer`, there are several classes
involved representing a File Object.

Next to the :php:`FileInterface` there is also the :php:`AbstractFile` class,
where most classes extend from when representing a File.

However, in order to ensure proper code strictness, the Abstract class does
not implement the methods :php:`getIdentifier()` and :php:`setIdentifier()`
anymore, as this is indeed part of the subclasses' job.

They are now implemented in the respective classes inheriting from
:php:`AbstractFile`.


Impact
======

In an unlikely case that the TYPO3's File Abstraction Layer is extended by
adding custom PHP classes extending from AbstractFile, this will result in a
fatal PHP error, as the new abstract methods :php:`getIdentifier()` and
:php:`setIdentifier()` are not implemented.


Affected installations
======================

TYPO3 installations with a custom File Abstraction Layer code extending the
actual file abstraction layer, which is highly unlikely.


Migration
=========

Implement the two methods :php:`getIdentifier()` and :php:`setIdentifier()` in
the custom File class extending :php:`AbstractFile`.

This can also be done in previous TYPO3 versions to make the code ready for
multiple TYPO3 versions.

..  index:: PHP-API, NotScanned, ext:core
