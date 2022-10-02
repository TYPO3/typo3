.. include:: /Includes.rst.txt

.. _feature-96935:

=============================================================
Feature: #96935 - New registration for linkvalidator linktype
=============================================================

See :issue:`96935`

Description
===========

The system extension `linkvalidator` uses so called `linktypes` for
checking different types of links, e.g. internal or external links.
All `linktypes` have to implement the :php:`LinktypeInterface`.

This fact is now used to automatically register the `linktypes`, based
on the interface, if :yaml:`autoconfigure` is enabled in :file:`Services.yaml`.
Alternatively, one can manually tag a custom `linktype` with the
:yaml:`linkvalidator.linktype` tag (see section "Migration" in the
:doc:`breaking changelog <Breaking-96935-RegisterLinkvalidatorLinktypesViaServiceConfiguration>`).

Due to the autoconfiguration, the identifier has to be provided by the
class directly, using the now required :php:`getIdentifier()` method.
When extending :php:`\TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype`
it's sufficient to set the `$identifier` class property.

Impact
======

`linktypes` are now automatically registered through the service configuration,
based on the implemented interface.

.. index:: Backend, LocalConfiguration, PHP-API, ext:linkvalidator
