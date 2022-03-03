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

Impact
======

It's now possible to configure specific routes for a module, which all can
target any controller / action combination.

.. index:: Backend, PHP-API, ext:backend
