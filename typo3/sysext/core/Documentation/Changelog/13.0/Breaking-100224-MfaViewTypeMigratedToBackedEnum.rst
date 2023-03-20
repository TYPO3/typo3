.. include:: /Includes.rst.txt

.. _breaking-100224-1688541732:

=======================================================
Breaking: #100224 - MfaViewType migrated to backed enum
=======================================================

See :issue:`100224`

Description
===========

The class :php:`\TYPO3\CMS\Core\Authentication\Mfa\MfaViewType` has been
migrated to a native PHP backed enum.


Impact
======

Since :php:`MfaViewType` is no longer a class, the existing class constants
are no longer available, but are enum instances instead.

In addition, it's not possible to instantiate the class anymore or call
the :php:`equals()` method.

The :php:`\TYPO3\CMS\Core\Authentication\Mfa\MfaProviderInterface`, which
all MFA providers need to implement, does now require the third argument
:php:`$type` of the :php:`handleRequest()` method to be a :php:`MfaViewType`
instead of a :php:`string`.


Affected installations
======================

All installations directly using the class constants, instantiating the
class or calling the :php:`equals()` method.

All extensions with custom MFA providers, which therefore implement the
:php:`handleRequest()` method.

Migration
=========

To access the string representation of a :php:`MfaViewType`, use the
corresponding :php:`value` property, e.g.
:php:`\TYPO3\CMS\Core\Authentication\Mfa\MfaViewType::SETUP->value` or on a
variable, use :php:`$type->value`.

Replace class instantiation by :php:`\TYPO3\CMS\Core\Authentication\Mfa\MfaViewType::tryFrom('setup')`.

Adjust your MFA providers :php:`handleRequest()` method to match the interface:

.. code-block:: php

    public function handleRequest(
        ServerRequestInterface $request,
        MfaProviderPropertyManager $propertyManager,
        MfaViewType $type
    ): ResponseInterface;

.. index:: Backend, PHP-API, NotScanned, ext:core
