.. include:: /Includes.rst.txt

.. _breaking-96575-1663324432:

=========================================
Breaking: #96575 - Update to CodeMirror 6
=========================================

See :issue:`96575`

Description
===========

TYPO3 Core now ships with CodeMirror v6.
CodeMirror is used as editor in the TYPO3 Backend for editing
HTML records, TypoScript templates and plaintext files in the file module.

Impact
======

Existing CodeMirror v5 addons and modes need to be adapted for CodeMirror v6
which brings a completely rewritten plugin infrastructure.

Affected installations
======================

TYPO3 Installations with third-party extensions that register
custom CodeMirror addons or modes.

Migration
=========

Please consult https://codemirror.net/docs/migration/ for details on
CodeMirror migration itself.

The TYPO3 integration has been adapted to reflect the changed modes and
addons in the `T3editor` configuration files:

Adapt the mode configuration in :file:`Configuration/Backend/T3editor/Modes.php`
to use :php:`JavaScriptModuleInstruction` statements that pick ES6 module
for a specific language mode. A RequireJS module like
:js:`codemirror/mode/css/css` is now shipped in `@codemirror/lang-css`:

..  code-block:: php

    use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

    return [
        'css' => [
            'module' => JavaScriptModuleInstruction::create('@codemirror/lang-css', 'css')->invoke(),
            'extensions' => ['css'],
        ],
    ];

Addons no longer bring :php:`cssFiles` or :php:`options`, but only consist
of a :php:`module` and an optional :php:`keymap` statement, as the `options`
interface is gone in CodeMirror v6 and stylesheets are to be embedded into
JavaScript.

See following example for the registration of the history addon via
:file:`Configuration/Backend/T3editor/Modes.php`:

..  code-block:: php

    use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

    return [
        'history' => [
            'module' => JavaScriptModuleInstruction::create('@codemirror/commands', 'history')->invoke(),
            'keymap' => JavaScriptModuleInstruction::create('@codemirror/commands', 'historyKeymap'),
        ],
    ];

.. index:: Backend, JavaScript, NotScanned, ext:t3editor
