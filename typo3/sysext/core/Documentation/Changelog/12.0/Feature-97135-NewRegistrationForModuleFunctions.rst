.. include:: /Includes.rst.txt

.. _feature-97135:

=======================================================
Feature: #97135 - New Registration for module functions
=======================================================

See :issue:`97135`

Description
===========

Previously, module functions could be added to modules such as
:guilabel:`Web > Info` or :guilabel:`Web > Template` via the
now removed global :php:`TBE_MODULES_EXT` array.

Since those functions are actually additional - "third-level" - modules,
they are now registered as such via the
:doc:`new Module Registration API <Feature-96733-NewBackendModuleRegistrationAPI>`,
in an extension's :file:`Configuration/Backend/Modules.php` file.

Next to the additional configuration options, e.g. for defining the position,
this also allows administrators to define access permissions via the module
access logic for those modules individually.

Additionally, the corresponding backend controller classes are now
able to make use of the :doc:`new ModuleData API <Feature-96895-IntroduceModuleDataObject>`.

Example
=======

Registration of an additional - "third-level" - module for
:guilabel:`Web > Template` in the :file:`Configuration/Backend/Modules.php`
file of an extension:

..  code-block:: php

    'web_ts_customts' => [
        'parent' => 'web_ts',
        'access' => 'user',
        'path' => '/module/web/typoscript/custom-ts',
        'iconIdentifier' => 'module-custom-ts',
        'labels' => [
            'title' => 'LLL:EXT:extkey/Resources/Private/Language/locallang.xlf:mod_title',
        ],
        'routes' => [
            '_default' => [
                'target' => CustomTsController::class . '::handleRequest',
            ],
        ],
        'moduleData' => [
            'someOption' => false,
        ],
    ],

Impact
======

Additional - "third-level" - modules are now registered in the
extension's :file:`Configuration/Backend/Modules.php` file, the
same way as main and submodules. This therefore allows those
modules to benefit from the same functionality.

.. index:: Backend, PHP-API, ext:backend
