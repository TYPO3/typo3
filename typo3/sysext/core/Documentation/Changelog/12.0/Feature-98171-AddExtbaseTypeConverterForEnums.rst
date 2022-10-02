.. include:: /Includes.rst.txt

.. _feature-98171-1660910151:

=====================================================
Feature: #98171 - Add Extbase TypeConverter for enums
=====================================================

See :issue:`98171`

Description
===========

Since PHP 8.1 provides enums, we can also use them in our Extbase actions.
A new TypeConverter
:php:`\TYPO3\CMS\Extbase\Property\TypeConverter\EnumConverter`
was added with this feature.

Example
=======

Given an enum like this one:

..  code-block:: php

    enum ClosedStates
    {
        case Hide;
        case Show;
        case All;
    }

We can now use it like this in any Extbase action:

..  code-block:: php

    public function overviewAction(ClosedStates $closed = ClosedStates::Hide): ResponseInterface
    {
        // ...
    }

The URL argument can be send as `[closed]=Show` and is automatically converted
to an instance of :php:`ClosedStates::Show`

Impact
======

Enums can now be used as Extbase action arguments.

.. index:: PHP-API, ext:extbase
