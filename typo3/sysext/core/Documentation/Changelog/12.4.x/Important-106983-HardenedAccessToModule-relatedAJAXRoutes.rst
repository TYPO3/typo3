..  include:: /Includes.rst.txt

..  _important-106983-1750962567:

==================================================================
Important: #106983 - Hardened access to module-related AJAX routes
==================================================================

See :issue:`106983`

Description
===========

AJAX routes which are exclusively used in a specific backend module can now be
configured to inherit access from the respective module. A new configuration
option :php:`inheritAccessFromModule` is introduced to control this behavior.
It is already added to several existing AJAX routes shipped by TYPO3 core.

Requests to routes with an appropriate access check in place will result in a
403 response if the current backend user lacks required permissions.

Example configuration
=====================

In the following example, the `mymodule_myroute` AJAX route inherits access
checks from the `mymodule` backend module:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Backend/AjaxRoutes.php

    return [
        'mymodule_myroute' => [
            'path' => '/mymodule/myroute',
            'target' => \MyVendor\MyExtension\Controller\MySpecialController::class . '::mySpecialAction',
            'inheritAccessFromModule' => 'mymodule',
        ],
    ];

..  index:: Backend
