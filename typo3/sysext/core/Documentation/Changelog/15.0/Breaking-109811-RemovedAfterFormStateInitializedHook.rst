..  include:: /Includes.rst.txt

..  _breaking-109811-1759230001:

============================================================
Breaking: #109811 - Removed "afterFormStateInitialized" hook
============================================================

See :issue:`109811`

Description
===========

The hook
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterFormStateInitialized']`
has been removed in favor of the PSR-14 event
:php:`\TYPO3\CMS\Form\Event\AfterFormStateInitializedEvent`.

In addition, the interface
:php:`\TYPO3\CMS\Form\Domain\Runtime\FormRuntime\Lifecycle\AfterFormStateInitializedInterface`
has been removed as it was only used by the hook.

Impact
======

Hook implementations registered under :php:`afterFormStateInitialized` are
no longer executed in TYPO3 v15.0 and later.

Classes implementing
:php:`\TYPO3\CMS\Form\Domain\Runtime\FormRuntime\Lifecycle\AfterFormStateInitializedInterface`
will cause a PHP fatal error.

Affected installations
======================

TYPO3 installations with custom extensions using this hook or implementing
:php:`AfterFormStateInitializedInterface` are affected.

The extension scanner reports any usage as a strong match.

Migration
=========

Register a PSR-14 event listener for
:php:`\TYPO3\CMS\Form\Event\AfterFormStateInitializedEvent` instead:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/MyAfterFormStateInitializedEventListener.php

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Form\Event\AfterFormStateInitializedEvent;

    #[AsEventListener(identifier: 'my-extension/after-form-state-initialized')]
    final readonly class MyAfterFormStateInitializedEventListener
    {
        public function __invoke(AfterFormStateInitializedEvent $event): void
        {
            // Access $event->formRuntime->getFormState() here
        }
    }

Remove the hook registration from :file:`ext_localconf.php` and the
:php:`AfterFormStateInitializedInterface` implementation from your hook class.

See also the :ref:`Feature entry <feature-109811-1759230000>`.

..  index:: Frontend, ext:form, FullyScanned
