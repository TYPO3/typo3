..  include:: /Includes.rst.txt

..  _breaking-110485-1786725459:

============================================================
Breaking: #110485 - Menu content object registration changed
============================================================

See :issue:`110485`

Description
===========

Menu content objects - the sub types of the :typoscript:`HMENU` content
object, for example :typoscript:`TMENU` - are now registered as tagged
services via dependency injection. This comes with the following breaking
changes:

-   The class
    :php:`\TYPO3\CMS\Frontend\ContentObject\Menu\MenuContentObjectFactory`
    has been removed. It was marked :php:`@internal`, but its method
    :php:`registerMenuType()` was the only way to add or override a menu
    type and was therefore used from :file:`ext_localconf.php`.

-   The exception
    :php:`\TYPO3\CMS\Frontend\ContentObject\Menu\Exception\NoSuchMenuTypeException`
    has been removed. An unregistered menu type is no longer signalled by
    an exception, the menu is simply not rendered - which is the behavior
    that was already visible from the outside, since all Core call sites
    caught the exception and continued silently.

-   :php:`\TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject`
    now declares a constructor which requires the service locator of all
    registered menu content objects as first argument. Subclasses which
    declare their own constructor must hand this argument to
    :php:`parent::__construct()`.

-   Menu content objects are never shared: every :typoscript:`HMENU` level
    is rendered by its own instance. Instantiating them through
    :php:`GeneralUtility::makeInstance()` is not supported anymore, they
    have to be retrieved from the service locator.

The constructor argument of :php:`AbstractMenuContentObject` is set by the
dependency injection container for all services tagged as
:yaml:`frontend.menucontentobject`, together with :yaml:`shared: false`.
Registering a menu type therefore only requires the tag itself.

Impact
======

Menu types registered via
:php:`MenuContentObjectFactory->registerMenuType()` in
:file:`ext_localconf.php` cause a fatal PHP error, since the class does
not exist anymore.

Custom classes extending :php:`AbstractMenuContentObject` with an own
constructor cause a fatal PHP error as soon as they are instantiated,
unless they pass the service locator on to :php:`parent::__construct()`.

Affected installations
======================

All installations with custom extensions registering an own menu type, or
overriding the :typoscript:`TMENU` implementation. Since the only menu type
shipped by TYPO3 Core is :typoscript:`TMENU`, most installations are not
affected. The extension scanner reports usages of
:php:`MenuContentObjectFactory` and :php:`registerMenuType()`.

Migration
=========

Remove the :php:`registerMenuType()` call from :file:`ext_localconf.php`
and register the menu content object as a tagged service instead. The
:yaml:`identifier` is the menu type as used in TypoScript and has to be
written in upper case:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    services:
      _defaults:
        autowire: true
        autoconfigure: true
        public: false

      My\Extension\ContentObject\Menu\FancyMenuContentObject:
        tags:
          - name: frontend.menucontentobject
            identifier: 'FANCYMENU'

The menu type can then be used in TypoScript as before:

..  code-block:: typoscript

    page.10 = HMENU
    page.10 {
        1 = FANCYMENU
        1 {
            NO = 1
        }
    }

An existing menu type - including :typoscript:`TMENU` - is overridden by
registering an own class with the same :yaml:`identifier`. As extensions
are loaded after TYPO3 Core, the last registration for an identifier wins.

Custom classes extending :php:`AbstractMenuContentObject` which declare
their own constructor have to accept and pass on the service locator:

..  code-block:: php
    :caption: EXT:my_extension/Classes/ContentObject/Menu/FancyMenuContentObject.php

    use Psr\Container\ContainerInterface;
    use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject;

    final class FancyMenuContentObject extends AbstractMenuContentObject
    {
        public function __construct(
            ContainerInterface $menuContentObjectLocator,
            private readonly MyOwnDependency $myOwnDependency,
        ) {
            parent::__construct($menuContentObjectLocator);
        }
    }

Code which created a menu content object through
:php:`MenuContentObjectFactory->getMenuObjectByType()` should inject the
locator of all registered menu content objects instead:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    services:
      My\Extension\DataProcessing\MyMenuProcessor:
        shared: false
        arguments:
          $menuContentObjectLocator: !tagged_locator { tag: 'frontend.menucontentobject', index_by: 'identifier' }

..  code-block:: php

    use Psr\Container\ContainerInterface;

    final class MyMenuProcessor
    {
        public function __construct(
            private readonly ContainerInterface $menuContentObjectLocator,
        ) {}

        public function process(): void
        {
            $menu = $this->menuContentObjectLocator->get('TMENU');
            // ...
        }
    }

..  index:: Frontend, PHP-API, TypoScript, PartiallyScanned, ext:frontend
