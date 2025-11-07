..  include:: /Includes.rst.txt

..  _deprecation-108008-1762896168:

======================================================
Deprecation: #108008 - Manual shortcut button creation
======================================================

See :issue:`108008`

Description
===========

Manually creating and adding :php:`ShortcutButton` instances to the button bar
is deprecated and will trigger a deprecation warning.

Controllers should use the new :php:`DocHeaderComponent::setShortcutContext()`
method instead, which automatically creates and positions the shortcut button.

Impact
======

Controllers that manually create and add :php:`ShortcutButton` to the button bar
will trigger a deprecation warning. The button will still work as expected.

Affected installations
======================

Installations with custom backend modules that manually create shortcut buttons.

Migration
=========

Replace manual shortcut button creation with the new API:

**Before**:

..  code-block:: php

    $shortcutButton = $this->componentFactory->createShortcutButton()
        ->setRouteIdentifier('my_module')
        ->setDisplayName('My Module')
        ->setArguments(['id' => $pageId]);
    $view->addButtonToButtonBar($shortcutButton);

**After**:

..  code-block:: php

    $view->getDocHeaderComponent()->setShortcutContext(
        routeIdentifier: 'my_module',
        displayName: 'My Module',
        arguments: ['id' => $pageId]
    );

..  index:: Backend, PHP-API, PartiallyScanned, ext:backend
