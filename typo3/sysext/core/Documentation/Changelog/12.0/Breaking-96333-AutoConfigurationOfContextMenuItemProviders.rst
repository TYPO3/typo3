.. include:: /Includes.rst.txt

.. _breaking-96333:

===================================================================
Breaking: #96333 - Auto configuration of ContextMenu item providers
===================================================================

See :issue:`96333`

Description
===========

ContextMenu item providers, implementing :php:`\TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface`
are now automatically registered by adding the :yaml:`backend.contextmenu.itemprovider`
tag, if :yaml:`autoconfigure` is enabled in :file:`Services.yaml`. The new
:php:`\TYPO3\CMS\Backend\ContextMenu\ItemProviders\ItemProvidersRegistry` then
automatically receives those services and registers them.

All Core item providers extend the :php:`AbstractProvider` class, which is
usually also used by extensions. Due to the auto configuration, the context
information (table, record identifier and context) is no longer passed to the
:php:`__construct()`, but instead to the new :php:`setContext()` method.

The :php:`setContext()` method is therefore required for all item providers.

Impact
======

The registration via :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders']`
isn't evaluated anymore.

The item providers are retrieved from the container and are no longer
instantiated while passing context information as constructor arguments.
The context information is now passed to :php:`setContext()`.

Affected Installations
======================

All extensions, registering custom ContextMenu item providers.

All extensions, extending :php:`AbstractProvider` and overwriting the
:php:`__construct()` method.

All extensions, not extending :php:`AbstractProvider`, but implementing
:php:`\TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface` directly.

Migration
=========

Remove :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders']`
from your :file:`ext_localconf.php` file. If :yaml:`autoconfigure` is
not enabled in your :file:`Configuration/Services.(yaml|php)` file,
manually configure your item providers with the
:yaml:`backend.contextmenu.itemprovider` tag.

If your item providers extend :php:`AbstractProvider` and overwrite the
:php:`__construct()` method, adjust the signature like shown below:

..  code-block:: php

    // Before

    class MyItemProvider extends AbstractProvider {

        public function __construct(string $table, string $identifier, string $context = '')
        {
            parent::__construct($table, $identifier, $context);

            // My custom code
        }
    }

    // After

    class MyItemProvider extends AbstractProvider {

        public function __construct()
        {
            parent::__construct();

            // My custom code
        }
    }

In case you rely on the arguments, previously passed to :php:`__construct()`,
you can override the new :php:`setContext()` method, which is executed
prior to any other action like :php:`canHandle()`.

..  code-block:: php

    // Before

    class MyItemProvider extends AbstractProvider {

        public function __construct(string $table, string $identifier, string $context = '')
        {
            parent::__construct($table, $identifier, $context);

            if ($table === 'my_table') {
                // Do something
            }
    }

    // After

    class MyItemProvider extends AbstractProvider {

        public function setContext(string $table, string $identifier, string $context = ''): void
        {
            parent::setContext($table, $identifier, $context);

            if ($table === 'my_table') {
                // Do something
            }
        }
    }

In case your item provider does not extend :php:`AbstractProvider`, but instead
implements the :php:`\TYPO3\CMS\Backend\ContextMenu\ItemProviders\ProviderInterface`
directly, add the new :php:`setContext()` to the item provider.

.. index:: Backend, PHP-API, FullyScanned, ext:backend
