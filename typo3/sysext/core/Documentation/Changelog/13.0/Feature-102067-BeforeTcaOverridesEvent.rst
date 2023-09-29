.. include:: /Includes.rst.txt

.. _feature-102067-1695985288:

=================================================
Feature: #102067 - PSR-14 BeforeTcaOverridesEvent
=================================================

See :issue:`102067`

Description
===========

A new PSR-14 :php:`\TYPO3\CMS\Core\Configuration\Event\BeforeTcaOverridesEvent`
has been introduced, enabling developers to listen to the state between loaded
base TCA and merging of TCA overrides.

Example
-------

..  code-block:: php

    <?php

    declare(strict_types=1);

    namespace Vendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Configuration\Event\BeforeTcaOverridesEvent;

    final class AddTcaBeforeTcaOverrides
    {
        #[AsEventListener('vendor/my-extension/before-tca-overrides')]
        public function __invoke(BeforeTcaOverridesEvent $event): void
        {
            $tca = $event->getTca();
            $tca['tt_content']['columns']['header']['config']['max'] = 100;
            $event->setTca($tca);
        }
    }


Impact
======

The new PSR-14 can be used to dynamically generate TCA and add it as additional
base TCA. This is especially useful for "TCA generator" extensions, which add
TCA based on another resource, while still enabling users to override TCA via
the known TCA overrides API.

.. note::

    Please note that TCA is always "runtime cached". This means that dynamic
    additions must never depend on runtime state, e.g. the current PSR-7
    request or similar, because such information might not even exist when
    the first call is e.g. done from CLI.

.. index:: Backend, PHP-API, TCA, ext:core
