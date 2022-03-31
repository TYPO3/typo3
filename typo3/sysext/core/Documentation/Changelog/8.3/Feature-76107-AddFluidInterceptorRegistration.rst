
.. include:: /Includes.rst.txt

====================================================
Feature: #76107 - Add fluid interceptor registration
====================================================

See :issue:`76107`

Description
===========

Interceptors in Fluid Standalone have been introduced to be able to change the template output.
The Fluid API already allows for registration of custom interceptors. Now it is possible to define
custom interceptors via the following option:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['fluid']['interceptors']

Interceptors registered here are added to the Fluid parser configuration.

Impact
======

Extensions are able to register custom interceptors using the available configuration in :php:`$TYPO3_CONF_VARS[fluid][interceptors]`.

Registered classes have to implement the `\TYPO3Fluid\Fluid\Core\Parser\InterceptorInterface`.

.. code-block:: php

   // Register an own interceptor to fluid parser configuration
   $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['interceptors'][\TYPO3\CMS\Fluid\Core\Parser\Interceptor\DebugInterceptor::class] =
      \TYPO3\CMS\Fluid\Core\Parser\Interceptor\DebugInterceptor::class;

.. code-block:: php

   use TYPO3Fluid\Fluid\Core\Parser\InterceptorInterface;
   use TYPO3Fluid\Fluid\Core\Parser\ParsingState;
   use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;

   class DebugInterceptor implements InterceptorInterface
   {
      public function process(NodeInterface $node, $interceptorPosition, ParsingState $parsingState) : NodeInterface
      {
         return $node;
      }

      public function getInterceptionPoints()
      {
         return [];
      }
   }

.. index:: PHP-API, LocalConfiguration, Fluid
