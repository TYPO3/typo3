.. include:: /Includes.rst.txt

===============
Extend T3editor
===============

Custom modes (used for syntax highlighting) and addons can be registered.

CodeMirror delivers a lot more modes and addons than registered in T3editor
by default.

More supported addons and modes are available at:

*   https://github.com/codemirror/CodeMirror/tree/5.27.4/addon
*   https://github.com/codemirror/CodeMirror/tree/5.27.4/mode

Prerequisites
=============

To do this, extensions may have these two files to feed T3editor:

*   :file:`Configuration/Backend/T3editor/Addons.php`
*   :file:`Configuration/Backend/T3editor/Modes.php`

Both files return an array, as known as in TCA and Backend Routes, for example.

.. _register_addon:

Register an addon
=================

To register an addon, the following code may be used:

.. code-block:: php
    :caption: :file:`EXT:myext/Configuration/Backend/T3editor/Addons.php`

    <?php

    return [
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
    ];

.. confval:: <identifier>

    :type: string
    :Required: true

    Represents the unique identifier of the module (`my/addon` in this example).

.. confval:: module

    :type: string
    :Required: true

    Holds the RequireJS namespace of the CodeMirror module. For custom
    modules placed in an extension, the known
    `TYPO3/CMS/Extension/Module` namespace must be used.

.. confval:: cssFiles

    :type: array

    Holds all CSS files that must be loaded for the module.

.. confval:: options

    :type: array

    Options that are used by the addon.

.. confval:: modes

    :type: array

    If set the addon is only loaded if any of the modes supplied here is used.


.. _register_mode:

Register a mode
===============

To register a mode, the following code may be used:

.. code-block:: php
    :caption: :file:`EXT:myext/Configuration/Backend/T3editor/Modes.php`

    <?php

    return [
        'css' => [
            'module' => 'cm/mode/css/css',
            'extensions' => ['css'],
        ],
    ];


.. confval:: <identifier>

    :type: string
    :Required: true

    Represents the unique identifier and format code of the mode
    (`css` in this example). The format code is used in TCA to
    define the CodeMirror mode to be used.

    Example::

        $GLOBALS['TCA']['tt_content']['types']['css']['columnsOverrides']['bodytext']['config']['format'] = 'css';

.. confval:: module

    :type: string
    :Required: true

    Holds the RequireJS namespace of the CodeMirror module. For custom
    modules placed in an extension, the known
    `TYPO3/CMS/Extension/Module` namespace must be used.

.. confval:: extensions

    :type: array

    Binds the mode to specific file extensions. This is important for using
    T3editor in the module :guilabel:`Filelist`.

.. confval:: default

    :type: bool

    If set, the mode is used as fallback if no sufficient mode is available.
    By factory default, the default mode is `html`.
