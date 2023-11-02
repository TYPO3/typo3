.. include:: /Includes.rst.txt

.. _breaking-102165-1698700428:

=================================================================================
Breaking: #102165 - File Abstraction Layer: Processing APIs and Interface changed
=================================================================================

See :issue:`102165`

Description
===========

The Task API for processing files (mainly images) in the File Abstraction Layer
has been reworked. This mainly accommodates to the fact, that the API was
revisited, the functionality has been updated to be up-to-date with PHP
standards and further adaptions.

The PHP interface :php:`TYPO3\CMS\Core\Resource\Processing\TaskInterface` has
lost the :php:`__construct` method as part of the interface, as the
constructor is an implementation detail and should not be part of an interface
definition. In addition, the method :php:`sanitizeConfiguration` has been added
to clean and sort the properties required for a task. All other methods have
been fully typed.

The PHP class :php:`TYPO3\CMS\Core\Resource\Processing\AbstractGraphicalTask`
has been removed in order to reduce complexity, as all of the methods have
been moved in to the respective subclasses.

The PHP class :php:`TYPO3\CMS\Core\Resource\Processing\Task` now has two
abstract methods :php:`getName()` and :php:`getType()` in favor of the protected
properties :php:`$name` and :php:`$type`.

The PHP class :php:`TYPO3\CMS\Core\Resource\ProcessedFile` is now fully typed.


Impact
======

Custom FAL Processing Tasks will result in a fatal error if not adapted to the
new interface.

If an extension was depending on :php:`AbstractGraphicalTask`, calling this
code will now result in a PHP fatal error.


Affected installations
======================

TYPO3 installations working with the internals of the Processing part of the
File Abstraction Layer, e.g. when extensions add custom FAL Processors or
custom Tasks.


Migration
=========

Implementing a custom FAL Processing Task will require the extension author to
adapt to the new interface requirements.

When a custom Task was built on top of the :php:`AbstractGraphicalTask`, this
now needs to be removed and be compliant with the TaskInterface, optionally
inheriting from the :php:`AbstractTask` object. This can already be achieved for
TYPO3 v12 to make an implementation compatible with TYPO3 v12 and TYPO3 v13.

.. index:: FAL, PHP-API, PartiallyScanned, ext:core
