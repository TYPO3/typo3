..  include:: /Includes.rst.txt

..  _feature-108008-1762896168:

===========================================================================
Feature: #108008 - Automatic reload and shortcut buttons in backend modules
===========================================================================

See :issue:`108008`

Description
===========

Backend modules now automatically get reload and shortcut buttons added to
their document header, ensuring consistent display across all backend modules.

Previously, controllers manually added these buttons with varying group
numbers, leading to inconsistent positioning. Now, buttons always appear on
the right side, ensuring they are always the last two buttons regardless of
what other buttons are added.

Controllers provide shortcut information using the new
:php:`DocHeaderComponent::setShortcutContext()` method:

..  code-block:: php

    use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;

    $view->getDocHeaderComponent()->setShortcutContext(
        routeIdentifier: 'site_configuration.edit',
        displayName: sprintf('Edit site: %s', $siteIdentifier),
        arguments: ['site' => $siteIdentifier]
    );

Buttons are automatically added during rendering before the PSR-14
:php:`ModifyButtonBarEvent` is dispatched, allowing event listeners to modify
or remove them if needed.

Controllers can disable automatic buttons if custom behavior is required:

..  code-block:: php

    use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;

    $view->getDocHeaderComponent()->disableAutomaticReloadButton();
    $view->getDocHeaderComponent()->disableAutomaticShortcutButton();

Impact
======

Reload and shortcut buttons now appear consistently at the same position
across all backend modules, providing a predictable user experience.

Controllers no longer need to manually create these buttons, reducing
boilerplate code. Use :php:`DocHeaderComponent::setShortcutContext()` to
provide shortcut information and remove manual button creation, see
:ref:`deprecation-108008-1762896168`.

**Before**:

..  code-block:: php

    use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
    use TYPO3\CMS\Backend\Template\Components\ButtonBar;

    $reloadButton = $this->componentFactory->createReloadButton($uri);
    $view->addButtonToButtonBar(
        $reloadButton,
        ButtonBar::BUTTON_POSITION_RIGHT,
        3
    );

    $shortcutButton = $this->componentFactory->createShortcutButton()
        ->setRouteIdentifier('my_module')
        ->setDisplayName('My Module')
        ->setArguments(['id' => $pageId]);
    $view->addButtonToButtonBar($shortcutButton);

**After**:

..  code-block:: php

    use TYPO3\CMS\Backend\Template\Components\DocHeaderComponent;

    // Set shortcut context only
    $view->getDocHeaderComponent()->setShortcutContext(
        routeIdentifier: 'my_module',
        displayName: 'My Module',
        arguments: ['id' => $pageId]
    );

..  index:: Backend, PHP-API, ext:backend
