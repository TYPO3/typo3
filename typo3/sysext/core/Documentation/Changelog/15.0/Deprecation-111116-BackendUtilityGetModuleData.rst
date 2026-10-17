..  include:: /Includes.rst.txt

..  _deprecation-111116-1792245903:

======================================================
Deprecation: #111116 - BackendUtility::getModuleData()
======================================================

See :issue:`111116`

Description
===========

The method
:php:`\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData()` has been
deprecated. It managed module settings in the backend user configuration
based on a :php:`$MOD_MENU` array, a pattern from before backend modules
were registered via :file:`Configuration/Backend/Modules.php`.

Backend modules receive their settings as
:php:`\TYPO3\CMS\Backend\Module\ModuleData` object in the request attribute
:php:`moduleData`, which is created, filled and persisted by the backend
module middleware.

Impact
======

Calling the method triggers a PHP :php:`E_USER_DEPRECATED` error. The
extension scanner reports usages.

Affected installations
======================

Installations with extensions calling
:php:`BackendUtility::getModuleData()` in custom backend modules.

Migration
=========

Declare the module settings and their default values in the
:php:`moduleData` option of the module registration. Values sent with the
request under the same names are then persisted automatically. Read the
settings from the :php:`moduleData` request attribute and use
:php:`ModuleData->cleanUp()` to restrict menu settings to allowed values.

Before:

..  code-block:: php

    use TYPO3\CMS\Backend\Utility\BackendUtility;

    $menu = [
        'mode' => ['list' => 'List', 'tiles' => 'Tiles'],
    ];
    $settings = BackendUtility::getModuleData(
        $menu,
        $request->getParsedBody()['SET'] ?? [],
        'my_module'
    );
    $mode = $settings['mode'];

After:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/Modules.php

    return [
        'my_module' => [
            // ...
            'moduleData' => [
                'mode' => 'list',
            ],
        ],
    ];

..  code-block:: php

    use TYPO3\CMS\Backend\Module\ModuleData;

    /** @var ModuleData $moduleData */
    $moduleData = $request->getAttribute('moduleData');
    if ($moduleData->clean('mode', ['list', 'tiles'])) {
        $this->getBackendUser()->pushModuleData(
            $moduleData->getModuleIdentifier(),
            $moduleData->toArray()
        );
    }
    $mode = $moduleData->get('mode');

..  index:: Backend, PHP-API, FullyScanned, ext:backend
