.. include:: /Includes.rst.txt

.. _feature-102628-1702031683:

===============================================
Feature: #102628 - Cache instruction middleware
===============================================

See :issue:`102628`

Description
===========

TYPO3 v13 introduces the new frontend-related PSR-7 request attribute :php:`frontend.cache.instruction`
implemented by class :php:`\TYPO3\CMS\Frontend\Cache\CacheInstruction`. This replaces the
previous :php:`TyposcriptFrontendController->no_cache` property and boolean :php:`noCache` request
attribute.

Impact
======

The attribute can be used by middlewares to disable cache mechanics of the frontend rendering.

In early middlewares before :php:`typo3/cms-frontend/tsfe`, the attribute may or may not exist
already. A safe way to interact with it is like this:

.. code-block:: php

    $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
    $cacheInstruction->disableCache('EXT:my-extension: My-reason disables caches.');
    $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);

Extension with middlewares or other code after :php:`typo3/cms-frontend/tsfe` can assume the attribute to
be set already. Usage example:

.. code-block:: php

    $cacheInstruction = $request->getAttribute('frontend.cache.instruction');
    $cacheInstruction->disableCache('EXT:my-extension: My-reason disables caches.');


.. index:: Frontend, PHP-API, ext:frontend
