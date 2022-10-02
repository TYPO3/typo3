.. include:: /Includes.rst.txt

.. _feature-96895:

==============================================
Feature: #96895 - Introduce Module data object
==============================================

See :issue:`96895`

Description
===========

To further improve the handling of TYPO3 backend modules, the module
registration API, introduced in :issue:`96733`, is extended by the new
:php:`TYPO3\CMS\Backend\Module\ModuleData` object.

The :php:`ModuleData` object contains the user specific module settings,
e.g. whether the clipboard is shown, for the requested module. Those settings
are fetched from the users' session. A PSR-15 middleware does automatically
create the object from the stored user data and attach it to the PSR-7 Request.

Through the module registration one can define, which properties can
be overwritten via :php:`GET` / :php:`POST` and their default value.

The whole determination is done before the requested route target - usually
a backend controller - is called. This means, the route target can just
read the final module data and does no longer have to fiddle around with
overwriting and persisting the data manually.

Previously, reading, overwriting and persisting of module data (settings)
was done in the controller:

..  code-block:: php

    // Classes/Controller/MyController.php

    $MOD_MENU = [
        'allowedProperty' => '',
        'anotherAllowedProperty' => true
    ];

    $MOD_SETTINGS = BackendUtility::getModuleData(
        $MOD_MENU,
        $request->getParsedBody()['SET'] ?? $request->getQueryParams()['SET'] ?? [],
        'my_module'
    );

This is now automatically done by a new PSR-15 middleware. The "allowed"
properties are defined with their default value in the module registration:

..  code-block:: php

    // Configuration/Backend/Modules.php

    'moduleData' => [
        'allowedProperty' => '',
        'anotherAllowedProperty' => true,
    ],

    // Classes/Controller/MyController.php

    $MOD_SETTINGS = $request->getAttribute('moduleData');

The :php:`ModuleData` object provides the following methods:

+-------------------------+-----------------------+----------------------------------------------------+
| Method                  | Parameters            | Description                                        |
+=========================+=======================+====================================================+
| createFromModule()      | :php:`$module`        | Create a new object for the given module, while    |
|                         | :php:`$data`          | overwriting the default values with :php:`$data`.  |
+-------------------------+-----------------------+----------------------------------------------------+
| getModuleIdentifier()   |                       | Returns the related module identifier              |
+-------------------------+-----------------------+----------------------------------------------------+
| get()                   | :php:`$propertyName`  | Returns the value for :php:`$propertyName`, or the |
|                         | :php:`$default`       | :php:`$default`, if not set.                       |
+-------------------------+-----------------------+----------------------------------------------------+
| set()                   | :php:`$propertyName`  | Updates :php:`$propertyName` with the given        |
|                         | :php:`$value`         | :php:`$value`.                                     |
+-------------------------+-----------------------+----------------------------------------------------+
| has()                   | :php:`$propertyName`  | Whether :php:`$propertyName` exists.               |
+-------------------------+-----------------------+----------------------------------------------------+
| clean()                 | :php:`$propertyName`  | Cleans a single property by the given allowed      |
|                         | :php:`$allowedValues` | list and falls back to either the default value    |
|                         |                       | or the first allowed value.                        |
+-------------------------+-----------------------+----------------------------------------------------+
| cleanUp()               | :php:`$allowedData`   | Cleans up all module data defined in the given     |
|                         | :php:`$useKeys`       | list of allowed data. Usually called with          |
|                         |                       | :php:`$MOD_MENU` in a controller with module menu. |
+-------------------------+-----------------------+----------------------------------------------------+
| toArray()               |                       | Returns the module data as :php:`array`.           |
+-------------------------+-----------------------+----------------------------------------------------+

In case a controller needs to store changed module data, this can still be done
using :php:`$backendUser->pushModuleData('my_module', $this->moduleData->toArray());`.

.. note::

    It's still possible to store and retrieve arbitrary module data. The
    definition of :php:`moduleData` in the module registration only defines,
    which properties can be overwritten in a request (with :php:`GET` / :php:`POST`).

To restrict the values of module data properties, the given :php:`ModuleData`
object can be cleaned e.g. in a controller:

..  code-block:: php

    $allowedValues = ['foo', 'bar'];

    $this->moduleData->clean('property', $allowedValues);

If :php:`ModuleData` contains :php:`property`, the value is checked
against the :php:`$allowedValues` list. If the current value is valid,
nothing happens. Otherwise the value is either changed to the default
or if this value is also not allowed, to the first allowed value.

Impact
======

The new :php:`ModuleData` object is available as new attribute of the
PSR-7 Request - in case a TYPO3 backend module is requested - and contains
the stored module data, which might have been overwritten through the current
request (with :php:`GET` / :php:`POST`).

.. index:: Backend, PHP-API, ext:backend
