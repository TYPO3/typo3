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

An example with the DefaultProvider:

.. code-block:: php

   $resolver = GeneralUtility::makeInstance(
      Resolver::class,
      'default',
      [
         'foo' => 1,
         'bar' => 2,
      ]
   );
   $resolver->evaluate('1 < 2'); // result is true
   $resolver->evaluate('foo < bar'); // result is true
   $resolver->evaluate('bar < foo'); // result is false


An example with a custom Provider:

First you have to configure a provider, create a file in your extension with the path and name :file:`EXT:my_ext/Configuration/ExpressionLanguage.php`:

.. code-block:: php

   <?php
      return [
         'my-context-identifier' => [
            \TYPO3\CMS\MyExt\ExpressionLanguage\MyCustomProvider::class,
         ]
      ];


Next implement your provider class :php:`\TYPO3\CMS\MyExt\ExpressionLanguage\MyCustomProvider::class`

.. code-block:: php

   class MyCustomProvider extends \TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider
   {
      public function __construct()
      {
         $this->expressionLanguageVariables = [
            'foo' => 1,
            'bar' => 2,
         ];
         $this->expressionLanguageProviders = [
            // We use the existing Typo3ConditionsFunctions...
            Typo3ConditionFunctionsProvider::class,
            // ... and our custom function provider
            MyCustomFunctionsProvider::class
         ];
      }
   }


Next implement your provider class :php:`\TYPO3\CMS\MyExt\ExpressionLanguage\MyCustomFunctionProvider::class`

.. code-block:: php

   class MyCustomFunctionProvider implements ExpressionFunctionProviderInterface
   {
      public function getFunctions()
      {
         return [
            $this->getFooFunction(),
         ];
      }

      protected function getFooFunction(): ExpressionFunction
      {
         return new ExpressionFunction('compatVersion', function ($str) {
            // Not implemented, we only use the evaluator
         }, function ($arguments, $str) {
            return $str === 'foo';
         });
      }
   }


And now we use it:

.. code-block:: php

   $resolver = GeneralUtility::makeInstance(
      Resolver::class,
      'my-context-identifier',
      [
         'baz' => 3,
      ]
   );
   $resolver->evaluate('1 < 2'); // result is true
   $resolver->evaluate('foo < bar'); // result is true
   $resolver->evaluate('bar < baz'); // result is true
   $resolver->evaluate('bar < foo'); // result is false
   $resolver->evaluate('foo("foo")'); // result is true
   $resolver->evaluate('foo("bar")'); // result is false


Impact
======

The expression language can now be used in other scopes and has no dependency to EXT:form.

.. index:: Backend, Frontend, PHP-API, ext:core
