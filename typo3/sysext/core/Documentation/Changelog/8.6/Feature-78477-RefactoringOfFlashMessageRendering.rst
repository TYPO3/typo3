.. include:: ../../Includes.txt

=======================================================
Feature: #78477 - Refactoring of FlashMessage rendering
=======================================================

See :issue:`78477`

Description
===========

The implementation of rendering FlashMessages in the core has been optimized.
With :issue:`73698` a cleanup has been started to centralize the markup within the FlashMessage class.

A new class called :php:`FlashMessageRendererResolver` has been introduced.
This class detects the context and renders the given FlashMessages in the correct output format.
It can handle any kind of output format.
The following FlashMessageRendererResolver classes have been introduced:

* :php:`TYPO3\CMS\Core\Messaging\Renderer\BootstrapRenderer` (is used in backend context by default)
* :php:`TYPO3\CMS\Core\Messaging\Renderer\ListRenderer` (is used in frontend context by default)
* :php:`TYPO3\CMS\Core\Messaging\Renderer\PlaintextRenderer` (is used in CLI context by default)

All new rendering classes have to implement the :php:`TYPO3\CMS\Core\Messaging\Renderer\FlashMessageRendererInterface` interface.


Impact
======

The core has been modified to use the new :php:`FlashMessageRendererResolver`.
Any third party extension should use the provided :php:`FlashMessageViewHelper` or the new :php:`FlashMessageRendererResolver` class:

.. code-block:: php

   $out = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)
      ->resolve()
      ->render($flashMessages);


.. index:: Backend, PHP-API
