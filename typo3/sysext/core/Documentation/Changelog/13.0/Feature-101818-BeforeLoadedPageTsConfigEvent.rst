.. include:: /Includes.rst.txt

.. _feature-101818-1693570608:

================================================
Feature: #101818 - BeforeLoadedPageTsConfigEvent
================================================

See :issue:`101818`

Description
===========

The PSR-14 event :php:`\TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedPageTsConfigEvent`
can be used to add global static page TSconfig before anything else is loaded.
This is especially useful, if page TSconfig is generated automatically as a
string from a PHP function.

It is important to understand that this config is considered static and thus
should not depend on runtime / request.

Example
-------

.. code-block:: php

    <?php

    namespace Vendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedPageTsConfigEvent;

    #[AsEventListener(identifier: 'vendor/my-extension/global-pagetsconfig')]
    final class AddGlobalPageTsConfig
    {
        public function __invoke(BeforeLoadedPageTsConfigEvent $event): void
        {
            $event->addTsConfig('global = a global setting');
        }
    }


Impact
======

Developers are able to define an event listener which is dispatched before any
other page TSconfig is loaded.

.. index:: Backend, PHP-API, ext:core
