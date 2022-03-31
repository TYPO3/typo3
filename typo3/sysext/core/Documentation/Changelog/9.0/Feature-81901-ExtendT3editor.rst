.. include:: /Includes.rst.txt

=================================
Feature: #81901 - Extend T3editor
=================================

See :issue:`81901`

Description
===========

Since the refactoring of the system extension `t3editor`, custom modes (used for syntax highlighting) and addons can be registered now.

Prerequisites
-------------

To do this, extensions may have now these two files to feed T3editor:

* :file:`Configuration/Backend/T3editor/Addons.php`
* :file:`Configuration/Backend/T3editor/Modes.php`

Both files return an array, as known as in TCA and Backend Routes, for example.


Register an addon
-----------------

To register an addon, the following code may be used:

.. code-block:: php

    'my/addon' => [
        'module' => 'cm/addon/my/addon',
        'cssFiles' => [
            'EXT:my_extension/Resources/Public/Css/MyAddon.css',
        ],
        'options' [
            'foobar' => 'baz',
        ],
        'modes' => ['htmlmixed', 'xml'],
    ],


+--------------+--------+-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Key          | Type   | Description                                                                                                                                                                 |
+==============+========+=============================================================================================================================================================================+
| <identifier> | string | Represents the unique identifier of the module (`my/addon` in this example)                                                                                                 |
+-----------------------+-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| module       | string | Mandatory: Holds the RequireJS namespace of the CodeMirror module. For custom modules placed in an extension, the known `TYPO3/CMS/Extension/Module` namespace must be used |
+-----------------------+-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| cssFiles     | array  | Holds all CSS files that must be loaded for the module                                                                                                                      |
+-----------------------+-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| options      | array  | Options that are used by the addon                                                                                                                                          |
+-----------------------+-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| modes        | array  | "Jails" the addon to specific modes. This means, the module is only loaded if any of the given modes is used                                                                |
+-----------------------+-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------+


Register a mode
---------------

To register a mode, the following code may be used:

.. code-block:: php

    'css' => [
        'module' => 'cm/mode/css/css',
        'extensions' => ['css'],
    ],


+--------------+--------+-------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| Key          | Type   | Description                                                                                                                                                             |
+==============+========+=========================================================================================================================================================================+
| <identifier> | string | Represents the unique identifier and format code of the mode (`css` in this example). The format code is used in TCA to define the CodeMirror mode to be used           |
+--------------+--------+-------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| module       | string | Mandatory: Holds the RequireJS namespace of the CodeMirror mode. For custom modules placed in an extension, the known `TYPO3/CMS/Extension/Mode` namespace must be used |
+--------------+--------+-------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| extensions   | array  | Binds the mode to specific file extension. This is important for using T3editor in the Filelist module.                                                                 |
+--------------+--------+-------------------------------------------------------------------------------------------------------------------------------------------------------------------------+
| default      | bool   | If set, the mode is used as fallback if no sufficient mode is available. By factory default, the default mode is `html`                                                 |
+-----------------------+-------------------------------------------------------------------------------------------------------------------------------------------------------------------------+


CodeMirror delivers a lot more modes and addons than registered in T3editor by default.
More supported addons and modes are available at:

* https://github.com/codemirror/CodeMirror/tree/5.27.4/addon
* https://github.com/codemirror/CodeMirror/tree/5.27.4/mode


Impact
======

After clearing the system caches of TYPO3, T3editor checks every extension for the files `Configuration/Backend/T3editor/Addons.php`
and `Configuration/Backend/T3editor/Modes.php`. The complete configuration of both is built and stored in the cache
afterwards.


Affected Installations
======================

All installations are affected.

.. index:: Backend, ext:t3editor
