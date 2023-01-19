.. include:: /Includes.rst.txt

.. _feature-99647-1674134370:

=====================================================
Feature: #99647 - Specific routes for backend modules
=====================================================

See :issue:`99647`

Description
===========

With :issue:`96733` the new Module Registration API has been introduced. One
of the main features is the explicit definition of the module routes. To
further improve the registration, it's now possible to define specific routes
for the modules, targeting any controller / action combination. Previously
the `target` of a module usually targeted a controller action like
:php:`handleRequest()`, which then forwarded the request internally to a
specific action, specified by e.g. a query argument. Such umbrella method can
now be omitted by directly using the target action as :php:`target` in the
module configuration.

Additionally, this also makes any HTTP method check in the controller
superfluous, since the allowed methods can now also directly be defined in the
module configuration for each sub route.

Example
-------

..  code-block:: php

    return [
        'my_module' => [
            'parent' => 'web',
            'path' => '/module/web/my-module',
            'routes' => [
                '_default' => [
                    'target' => MyModuleController::class . '::overview',
                ],
                'edit' => [
                    'path' => '/custom-path',
                    'target' => MyModuleController::class . '::edit',
                ],
                'manage' => [
                    'target' => AnotherController::class . '::manage',
                    'methods' => ['POST'],
                ],
            ],
        ],
    ];

In case the :php:`path` option is omitted for a sub route, its identifier is
automatically used as :php:`path`, e.g. :php:`/manage`.

All sub routes are automatically registered in a :php:`RouteCollection`. The
full route identifier syntax is :php:`<module_identifier>.<sub_route>`, e.g.
:php:`my_module.edit`. Using the :php:`UriBuilder` to create a link to such
sub route could therefore look like this:

..  code-block:: php

    UriBuilder->buildUriFromRoute('my_module.edit')

Extbase modules
^^^^^^^^^^^^^^^

Also Extbase Backend Modules are enhanced and do now automatically
define explicit routes for each controller / action combination,
as long as the :typoscript:`enableNamespacedArgumentsForBackend`
feature toggle is turned off, which is the default. This means,
the following module configuration

..  code-block:: php

    return [
        'web_ExtkeyExample' => [
            'parent' => 'web',
            'position' => ['after' => 'web_info'],
            'access' => 'admin',
            'workspaces' => 'live',
            'iconIdentifier' => 'module-example',
            'path' => '/module/web/ExtkeyExample',
            'labels' => 'LLL:EXT:beuser/Resources/Private/Language/locallang_mod.xlf',
            'extensionName' => 'Extkey',
            'controllerActions' => [
                MyModuleController::class => [
                    'list',
                    'detail'
                ],
            ],
        ],
    ];

now leads to following URLs:

- `https://example.com/typo3/module/web/ExtkeyExample`
- `https://example.com/typo3/module/web/ExtkeyExample/MyModuleController/list`
- `https://example.com/typo3/module/web/ExtkeyExample/MyModuleController/detail`

The route identifier of corresponding routes is registered with similar syntax
as standard backend modules: :php:`<module_identifier>.<controller>_<action>`.
Above configuration will therefore register the following routes:

- `web_ExtkeyExample`
- `web_ExtkeyExample.MyModuleController_list`
- `web_ExtkeyExample.MyModuleController_detail`

Impact
======

It's now possible to configure specific routes for a module, which all can
target any controller / action combination.

As long as :typoscript:`enableNamespacedArgumentsForBackend` is turned off
for Extbase Backend Modules, all controller / action combinations are explicitly
registered as individual routes. This effectively means human-readable URLs,
since the controller / action combinations are no longer defined via query
parameters but are now part of the path.

.. index:: Backend, PHP-API, ext:backend
