.. include:: /Includes.rst.txt

=======================================================
Deprecation: #94317 - ext:form Finisher implementations
=======================================================

See :issue:`94317`

Description
===========

In preparation of the Extbase ObjectManager deprecation in favor of
symfony dependency injection, some details of EXT:form finishers had
to be adapted: In contrast to Extbase object management, symfony DI does
not support prototype classes with a mixture of manual constructor arguments,
plus dependency injection via other constructor arguments or inject methods.

The EXT:form finishers based on :php:`TYPO3\CMS\Form\Domain\Finishers\FinisherInterface`
relied on this and had to be adapted: The default constructor argument
:php:`$finisherIdentifier` has been dropped, so finisher implementations can
keep using dependency injection.


Impact
======

A compatibility layer detects non-adapted finishers and falls back to
initialization using Extbase ObjectManager. This will will trigger a
PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

In general only instances with custom form based on EXT:form are affected, and
only if they implement custom finishers.

Most custom finishers probably extend :php:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`.
Those are only affected if they override :php:`__construct()` or use or manipulate
properties :php:`$finisherIdentifier` or :php:`$shortFinisherIdentifier` in
:php:`inject*()` or :php:`injectObject()` methods. This is rather unlikely.

Custom finishers that do not extend :php:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`
are affected.


Migration
=========

Custom finishers should extend :php:`TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher`.

If they must implement :php:`__construct()`, they should not expect :php:`$finisherIdentifier`
to be hand over as first argument and must not call :php:`parent::construct()` anymore.

Custom finishers must not rely on :php:`$finisherIdentifier` or :php:`$shortFinisherIdentifier`
being set in early methods like :php:`__construct()`, :php:`inject*()` and :php:`injectObject()`,
and must not set these properties.

Custom finishers must implement method :php:`setFinisherIdentifier()`, this method will
be added to :php:`TYPO3\CMS\Form\Domain\Finishers\FinisherInterface` in TYPO3 v12.

Custom finishers must not use class property :php:`$objectManager` since this will vanish
in v12. This will affect more API cases and will have a dedicated deprecation file
with more details, though.

.. index:: PHP-API, NotScanned, ext:form
