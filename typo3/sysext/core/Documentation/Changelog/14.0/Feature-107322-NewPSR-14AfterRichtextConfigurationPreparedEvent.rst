..  include:: /Includes.rst.txt

..  _feature-107322-1755854784:

=====================================================================
Feature: #107322 - New PSR-14 AfterRichtextConfigurationPreparedEvent
=====================================================================

See :issue:`107322`

Description
===========

A new PSR-14 event
:php-short:`\TYPO3\CMS\Core\Configuration\Event\AfterRichtextConfigurationPreparedEvent`
has been added.

To modify the configuration, the following methods are available:

*   :php:`setConfiguration()`
*   :php:`getConfiguration()`

Example
=======

The corresponding event listener class:

..  code-block:: php
    :caption: Example event listener class

    <?php

    namespace MyVendor\MyExtension\Configuration\EventListener;

    use TYPO3\CMS\Core\Configuration\Event\AfterRichtextConfigurationPreparedEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class AfterRichtextConfigurationPreparedEventListener
    {
        #[AsEventListener('my_extension/configuration/modify-rich-text-configuration')]
        public function __invoke(AfterRichtextConfigurationPreparedEvent $event): void
        {
            $config = $event->getConfiguration();
            $config['editor']['config']['debug'] = true;
            $event->setConfiguration($config);
        }
    }

Impact
======

It is now possible to modify the rich-text configuration after it has been
fetched and prepared.

..  index:: PHP-API, TCA, ext:core
