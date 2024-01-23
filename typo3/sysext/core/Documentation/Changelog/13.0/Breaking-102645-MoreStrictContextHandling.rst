.. include:: /Includes.rst.txt

.. _breaking-102645-1702194379:

================================================
Breaking: #102645 - More strict Context handling
================================================

See :issue:`102645`

Description
===========

Class :php:`\TYPO3\CMS\Core\Context\Context` is a stateful singleton class set up
pretty early by the frontend or backend application after the request object has been created.
Its state is then further changed by various frontend and backend middlewares. It can
be retrieved using dependency injection or :php:`GeneralUtility::makeInstance()` in consuming
classes.

To clean up Context-related code a bit, the following changes have been made:

* Method :php:`__construct()` removed from :php:`\TYPO3\CMS\Core\Context\Context`
* Class :php:`\TYPO3\CMS\Core\Context\ContextAwareInterface` removed
* Trait :php:`\TYPO3\CMS\Core\Context\ContextAwareTrait` removed


Impact
======

Handing over manual arguments to the constructor of :php:`__construct()` does not have
an effect anymore, and using the interface or the trait will raise a fatal PHP error.


Affected installations
======================

Most likely, not too many instances are affected: An instance of :php:`Context` is
typically created by Core bootstrap and retrieved using dependency injection, extensions
usually do not need to create own instances.

There are also not many routing aspects with context dependencies that may use the
interface or the trait. If so, they can adapt easily and stay compatible with older
versions.


Migration
=========

The constructor of the Context class was bogus. Since the class is an injectable singleton
that should be available through the container, it must not have manual constructor arguments
since this would shut down the container registration. Extensions typically did not create
own instances of :php:`Context`, using the constructor argument was - if at all - only done
in tests. Unit tests should typically create own instances using :php:`new` and hand them
over to classes that get the context injected.

Adaption to the interface and trait removal is straight forward as well: Get the
context injected into the aspect, or retrieve the instance using :php:`GeneralUtility::makeInstance()`.


.. index:: PHP-API, PartiallyScanned, ext:core
