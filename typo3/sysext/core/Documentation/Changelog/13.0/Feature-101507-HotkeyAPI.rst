.. include:: /Includes.rst.txt

.. _feature-101507-1690808401:

=============================
Feature: #101507 - Hotkey API
=============================

See :issue:`101507`

Description
===========

TYPO3 provides the :js:`@typo3/backend/hotkeys.js` module that allows developers
to register custom keyboard shortcuts in the TYPO3 backend.

It is also possible and highly recommended to register hotkeys in a dedicated
scope to avoid conflicts with other hotkeys, perhaps registered by other
extensions.

The module provides an enum with common modifier keys (:kbd:`Ctrl`, :kbd:`Meta`,
:kbd:`Alt`, and :kbd:`Shift`), and also a public property describing the common
hotkey modifier based on the user's operating system: :kbd:`Cmd` (Meta) on macOS,
:kbd:`Ctrl` on anything else. Using any modifier is optional, but highly
recommended.

A hotkey is registered with the :js:`register()` method. The method takes three
arguments:

*   :js:`hotkey` - An array defining the keys that must be pressed
*   :js:`handler` - A callback that is executed when the hotkey is invoked
*   :js:`options` - Object that configured a hotkey's behavior.

    *   :js:`scope` - The scope a hotkey is registered in
    *   :js:`allowOnEditables` - If :js:`false` (default), handlers are not executed when an editable element is focussed
    *   :js:`allowRepeat` - If :js:`false` (default), handlers are not executed when the hotkey is pressed for a long time
    *   :js:`bindElement` - If given, an `aria-keyshortcuts` attribute is added to the element. This is recommended for accessibility reasons.

..  code-block:: js

    import Hotkeys, {ModifierKeys} from '@typo3/backend/hotkeys.js';

    Hotkeys.register(
        [Hotkeys.normalizedCtrlModifierKey, ModifierKeys.ALT, 'e'],
        function (keyboardEvent) => {
            console.log('Triggered on Ctrl/Cmd+Alt+E');
        },
        {
            scope: 'my-extension/module',
            bindElement: document.querySelector('.some-element')
        }
    );

    // Get the currently active scope
    const currentScope = Hotkeys.getScope();

    // Make use of registered scope
    Hotkeys.setScope('my-extension/module');

..  note::

    TYPO3 specific hotkeys may be registered in the reserved `all` scope.
    When invoking a hotkey from a different scope, the `all` scope is handled in
    any case at first.


Impact
======

If properly used, common functionality is easier to access with hotkeys. The
following hotkeys are configured within TYPO3:

* :kbd:`Ctrl/Cmd` + :kbd:`K` - open LiveSearch
* :kbd:`Ctrl/Cmd` + :kbd:`S` - save current document opened in FormEngine


.. index:: Backend, JavaScript, ext:backend
