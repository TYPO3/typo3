.. include:: /Includes.rst.txt

.. _deprecation-102326-1699703964:

=================================================================================
Deprecation: #102326 - RegularExpressionValidator validator option "errorMessage"
=================================================================================

See :issue:`102326`

Description
===========

The  :php:`errorMessage` validator option provides a custom string
as error message for validation failures of the :php:`RegularExpressionValidator`.
In order to streamline error message translation keys with other validators,
the :php:`errorMessage` validator option has been marked as deprecated in
TYPO3 v13 and will be removed in TYPO3 v14.


Impact
======

Using the :php:`errorMessage` validator option with the :php:`RegularExpressionValidator`
will trigger a PHP deprecation warning.


Affected installations
======================

TYPO3 installations using the validator option :php:`errorMessage` with the
:php:`RegularExpressionValidator`.


Migration
=========

The new :php:`message` validator option should be used to provide a custom
translatable error message for failed validation.

Before:

..  code-block:: php

    use TYPO3\CMS\Extbase\Annotation as Extbase;

    #[Extbase\Validate([
        'validator' => 'RegularExpression',
        'options' => [
            'regularExpression' => '/^simple[0-9]expression$/',
            'errorMessage' => 'Error message or LLL schema string',
        ],
    ])]
    protected string $myProperty = '';

After:

..  code-block:: php

    use TYPO3\CMS\Extbase\Annotation as Extbase;

    #[Extbase\Validate([
        'validator' => 'RegularExpression',
        'options' => [
            'regularExpression' => '/^simple[0-9]expression$/',
            'message' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:my.languageKey'
        ],
    ])]
    protected string $myProperty = '';

.. index:: Backend, NotScanned, ext:extbase
