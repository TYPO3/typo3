.. include:: /Includes.rst.txt

=====================================================================
Feature: #91719 - Custom error messages in RegularExpressionValidator
=====================================================================

See :issue:`91719`

Description
===========

The :php:`RegularExpressionValidator` can now return a custom validation error message
to help the user providing valid input.


Impact
======

A new option :php:`errorMessage` has been introduced to the validator.

Example:

.. code-block:: php

   class MyModel extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
   {
       /**
        * @var string
        * @TYPO3\CMS\Extbase\Annotation\Validate(
        *    "RegularExpression",
        *    options={
        *       "regularExpression": "/^SO[0-9]$/",
        *       "errorMessage": "explain how to provide a valid value"
        *    }
        * )
        */
       protected $customField;
   }

It is also possible to provide a translation key to render a localized message:

.. code-block:: php

   class MyModel extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
   {
       /**
        * @var string
        * @TYPO3\CMS\Extbase\Annotation\Validate(
        *    "RegularExpression",
        *    options={
        *       "regularExpression": "/^SO[0-9]$/",
        *       "errorMessage": "LLL:EXT:my_extension/path/to/xlf:translation.key"
        *    }
        * )
        */
       protected $customField;
   }

If no :php:`errorMessage` is provided, the default message will be displayed in case of a validation error.

.. index:: Frontend, ext:extbase
