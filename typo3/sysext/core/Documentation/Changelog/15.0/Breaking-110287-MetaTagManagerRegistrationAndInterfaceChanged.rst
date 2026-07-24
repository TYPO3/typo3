..  include:: /Includes.rst.txt

..  _breaking-110287-1784904501:

=======================================================================
Breaking: #110287 - Meta tag manager registration and interface changed
=======================================================================

See :issue:`110287`

Description
===========

Meta tag managers are now registered as tagged services via dependency
injection (see :ref:`feature-110287-1784904501`). This comes with the
following breaking changes:

-   :php:`\TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry->registerManager()`
    is now a no-op. Calling the method has no effect anymore. The method
    will be removed in TYPO3 v16.0.

-   The method :php:`removeAllManagers()` has been removed from
    :php:`MetaTagManagerRegistry`, since the list of managers is
    compiled into the dependency injection container and cannot be
    changed at runtime anymore.

-   The methods :php:`addProperty()`, :php:`removeProperty()` and
    :php:`removeAllProperties()` of
    :php:`\TYPO3\CMS\Core\MetaTag\MetaTagManagerInterface` now declare
    a native :php:`void` return type.

Impact
======

Meta tag managers registered via
:php:`MetaTagManagerRegistry->registerManager()` in
:file:`ext_localconf.php` are no longer evaluated: their meta tag
properties are handled by the generic meta tag manager until the manager
is registered as a tagged service.

Custom classes implementing :php:`MetaTagManagerInterface` directly
without the adapted method signatures will cause a fatal PHP error.
Classes extending :php:`\TYPO3\CMS\Core\MetaTag\AbstractMetaTagManager`
are only affected if they override one of the changed methods.

Affected installations
======================

All installations with custom extensions registering meta tag managers
via :php:`MetaTagManagerRegistry->registerManager()`, or providing custom
implementations of :php:`MetaTagManagerInterface`. The extension scanner
reports usages of the changed methods as weak match.

Migration
=========

Remove the :php:`MetaTagManagerRegistry->registerManager()` call from
:file:`ext_localconf.php` and add the :php:`#[AsMetaTagManager]`
attribute to the manager class instead:

..  code-block:: php
    :caption: EXT:my_extension/Classes/MetaTag/MyMetaTagManager.php

    use TYPO3\CMS\Core\Attribute\AsMetaTagManager;
    use TYPO3\CMS\Core\MetaTag\AbstractMetaTagManager;

    #[AsMetaTagManager(identifier: 'my-manager')]
    final class MyMetaTagManager extends AbstractMetaTagManager
    {
        // ...
    }

Move the manager name previously passed as first argument to
:php:`registerManager()` to the :php:`identifier` argument of the
attribute, and any :php:`before`/:php:`after` arguments to the
corresponding attribute arguments.

In case the extension supports both TYPO3 v14 and v15, register the
manager in both ways: the :file:`ext_localconf.php` registration is
evaluated in v14, the attribute in v15. The :php:`void` return type
declarations are compatible with both versions.

..  index:: PHP-API, Frontend, PartiallyScanned, ext:core
