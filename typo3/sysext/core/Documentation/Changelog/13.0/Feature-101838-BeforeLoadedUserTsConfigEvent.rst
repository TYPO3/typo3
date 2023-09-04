.. include:: /Includes.rst.txt

.. _feature-101838-1693834389:

================================================
Feature: #101838 - BeforeLoadedUserTsConfigEvent
================================================

See :issue:`101838`

Description
===========

The PSR-14 event :php:`\TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedUserTsConfigEvent`
can be used to add global static user TSconfig before anything else is loaded.
This is especially useful, if user TSconfig is generated automatically as a
string from a PHP function.

It is important to understand that this config is considered static and thus
should not depend on runtime / request.


Example
-------

..  code-block:: php

    <?php

    namespace Vendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedUserTsConfigEvent;

    #[AsEventListener(identifier: 'vendor/my-extension/global-usertsconfig')]
    final class AddGlobalUserTsConfig
    {
        public function __invoke(BeforeLoadedUserTsConfigEvent $event): void
        {
            $event->addTsConfig('global = a global setting');
        }
    }


Impact
======

Developers are able to define an event listener which is dispatched before any
other user TSconfig is loaded.

.. index:: Backend, PHP-API, TSConfig, ext:core
