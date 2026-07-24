..  include:: /Includes.rst.txt

..  _feature-110287-1784904501:

================================================================
Feature: #110287 - Register meta tag managers as tagged services
================================================================

See :issue:`110287`

Description
===========

Meta tag managers, used to manage and render the :html:`<meta>` tags of a
page, are now registered as tagged services via dependency injection
instead of the previous programmatic registration through
:php:`\TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry->registerManager()`
in :file:`ext_localconf.php`.

A meta tag manager class implementing
:php:`\TYPO3\CMS\Core\MetaTag\MetaTagManagerInterface` (usually by
extending :php:`\TYPO3\CMS\Core\MetaTag\AbstractMetaTagManager`) is
registered by adding the new PHP attribute
:php:`\TYPO3\CMS\Core\Attribute\AsMetaTagManager` to the class:

..  code-block:: php
    :caption: EXT:my_extension/Classes/MetaTag/MyMetaTagManager.php

    use TYPO3\CMS\Core\Attribute\AsMetaTagManager;
    use TYPO3\CMS\Core\MetaTag\AbstractMetaTagManager;

    #[AsMetaTagManager(identifier: 'my-manager')]
    final class MyMetaTagManager extends AbstractMetaTagManager
    {
        // ...
    }

The manager order is defined at registration time via the optional
:php:`before` and :php:`after` attribute arguments, referencing the
identifiers of other managers. A manager without constraints is ordered
before the :php:`generic` manager shipped with TYPO3 Core, which handles
every property and therefore always comes last — this matches the
previous default of :php:`registerManager()`.

..  code-block:: php

    #[AsMetaTagManager(identifier: 'my-manager', before: ['opengraph'], after: ['html5'])]

Alternatively, the service tag :yaml:`metatag.manager` can be used
directly in :file:`Configuration/Services.yaml`, with :yaml:`before` and
:yaml:`after` given as comma-separated lists:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    MyVendor\MyExtension\MetaTag\MyMetaTagManager:
      tags:
        - name: metatag.manager
          identifier: 'my-manager'

Impact
======

Registering meta tag managers as tagged services has the following
benefits over the previous programmatic registration:

-   The registration and the resolution of the :php:`before`/:php:`after`
    ordering constraints happen once at container compile time instead of
    executing registration code from :file:`ext_localconf.php` and
    sorting the managers on every request.

-   Meta tag managers are proper services now and can use dependency
    injection in their constructor.

-   The registration is validated at container compile time: a service
    tagged as :yaml:`metatag.manager` that does not implement
    :php:`MetaTagManagerInterface`, or is missing the :yaml:`identifier`
    tag attribute, fails the container build with a speaking exception.

Registration in :file:`ext_localconf.php` via
:php:`MetaTagManagerRegistry->registerManager()` is not evaluated
anymore, see :ref:`breaking-110287-1784904501` for the upgrade path.

..  index:: PHP-API, Frontend, ext:core
