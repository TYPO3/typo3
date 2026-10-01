..  include:: /Includes.rst.txt

..  _feature-109846-1788965266:

=========================================================
Feature: #109846 - TCA type "email" supports eval "lower"
=========================================================

See :issue:`109846`

Description
===========

TCA fields with :php:`'type' => 'email'` now support the `eval` keyword
`lower`, which has been available for :php:`'type' => 'input'` before:

..  code-block:: php

    'author_email' => [
        'label' => 'Author email',
        'config' => [
            'type' => 'email',
            'eval' => 'lower',
        ],
    ],

The backend form lowercases the value while the record is edited, and
:php-short:`\TYPO3\CMS\Core\DataHandling\DataHandler` does so again when the record is saved.
Lowercasing happens before the `unique` and `uniqueInPid`
evaluations, so two addresses that differ in casing
only are detected as the same value.

Impact
======

Email addresses can be stored in a normalized, lowercase form, which makes
them comparable - for instance when a field is additionally configured with
`unique`, or when the value is used to look up records.

..  index:: TCA, ext:core
