.. include:: /Includes.rst.txt

======================================================
Important: #88720 - Respect site for persisted mappers
======================================================

See :issue:`88720`

Description
===========

:php:`\TYPO3\CMS\Core\Routing\Aspect\AspectFactory::createAspects()` signature
requires was extended with mandatory argument `\TYPO3\CMS\Core\Site\Entity\Site $site`
and is now defined like

.. code-block:: php

   public function createAspects(array $aspects, SiteLanguage $language, Site $site): array

Extensions using :php:`\TYPO3\CMS\Core\Routing\Aspect\AspectFactory::createAspects()`
have to be upgraded to pass all mandatory arguments.

.. index:: Backend, Frontend, PHP-API, FullyScanned

