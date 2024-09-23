.. include:: /Includes.rst.txt

.. _feature-102326-1699707043:

===================================================================
Feature: #102326 - Allow custom translations for Extbase validators
===================================================================

See :issue:`102326`

Description
===========

All validation messages from Extbase validators can now be overwritten
using validator options. It is possible to provide either a translation key or
a custom message as string.

Extbase validators providing only one validation message can be overwritten by a
translation key or message using the validator option :php:`message`. Validators
providing multiple validation messages (e.g. :php:`Boolean`, :php:`NotEmpty` or
:php:`NumberRange`) use different validator options keys. In general,
translation keys or messages for validators are registered in the validator
property :php:`translationOptions`.

Example with translations
-------------------------

..  code-block:: php

    use TYPO3\CMS\Extbase\Annotation as Extbase;

    #[Extbase\Validate([
        'validator' => 'NotEmpty',
        'options' => [
            'nullMessage' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:validation.myProperty.notNull',
            'emptyMessage' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:validation.myProperty.notEmpty',
        ],
    ])]
    protected string $myProperty = '';

In this example, translation option keys for the :php:`NotEmptyValidator` are
overwritten for the property :php:`$myProperty`. The :php:`locallang.xlf`
translation file from the extension :php:`my_extension` will be used to lookup
translations for the newly provided translation key options.

Example with a custom string
----------------------------

..  code-block:: php

    use TYPO3\CMS\Extbase\Annotation as Extbase;

    #[Extbase\Validate([
        'validator' => 'Float',
        'options' => [
            'message' => 'A custom, non translatable message',
        ],
    ])]
    protected float $myProperty = 0.0;

In this example, translation option keys for the :php:`FloatValidator` are
overwritten for the property :php:`$myProperty`. The message string is
shown if validation fails.

Impact
======

The new validator translation option keys allow developers to define unique
validation messages for TYPO3 Extbase validators on validator usage basis. This
may result in a better user experience, since validation messages now can refer
to the current usage scope (e.g. "The field 'Title' is required" instead of
"The given subject was empty.").

.. index:: Backend, ext:extbase
