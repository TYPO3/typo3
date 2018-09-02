.. include:: ../../Includes.txt

=========================================================================
Feature: #85828 - Move symfony expression language handling into EXT:core
=========================================================================

See :issue:`85828`

Description
===========

The symfony expression language handling has been moved out of EXT:form into EXT:core.
Thus, it is available to be used throughout the core and also for extension developers.

To use the expression language, a provider definition is required which implements the
:php:`\TYPO3\CMS\Core\ExpressionLanguage\ProviderInterface`.
The core comes with a :php:`\TYPO3\CMS\Core\ExpressionLanguage\DefaultProvider` class which can be used directly.
For a custom implementation the :php:`\TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider` class can be extended.

The provider can provide additional variables and expression functions to extend the expression language.
For a custom implementation check out the :php:`\TYPO3\CMS\Form\Domain\Condition\ConditionProvider` class.

A usage example:

.. code-block:: php

   $provider = GeneralUtility::makeInstance(\TYPO3\CMS\Core\ExpressionLanguage\DefaultProvider::class);
   $conditionResolver = GeneralUtility::makeInstance(\TYPO3\CMS\Core\ExpressionLanguage\Resolver::class, $provider);
   $conditionResolver->evaluate('1 < 2'); // result is true


Impact
======

The expression language can now be used in other scopes and has no dependency to EXT:form.

.. index:: Backend, Frontend, PHP-API, ext:core
