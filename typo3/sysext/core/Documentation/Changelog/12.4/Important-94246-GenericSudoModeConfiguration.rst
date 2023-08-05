.. include:: /Includes.rst.txt

.. _important-94246-1681366863:

===================================================
Important: #94246 - Generic sudo mode configuration
===================================================

See :issue:`94246`

Description
===========

:doc:`Sudo mode <../9.5.x/Important-92836-IntroduceSudoModeForInstallToolAccessedViaBackend>`
has been integrated since TYPO3 v9.5.x to protect only Install Tool components. With TYPO3 v12
it has been changed to a generic configuration for backend routes (and implicitly modules).

Besides that, access to the Extension Manager now needs to pass the sudo mode verification as well.


Process in a nutshell
---------------------

All simplified classnames below are located in the namespace :php:`\TYPO3\CMS\Backend\Security\SudoMode\Access`).
The low-level request orchestration happens in the middleware :php:`\TYPO3\CMS\Backend\Middleware\SudoModeInterceptor`,
markup rendering and payload processing in controller :php:`\TYPO3\CMS\Backend\Controller\Security\SudoModeController`.

#.  A backend route is processed, that requires sudo mode for route URI `/my/route`
    in :php:`\TYPO3\CMS\Backend\Http\RouteDispatcher`.
#.  Using :php:`AccessFactory` and :php:`AccessStorage`, the :php:`RouteDispatcher`
    tries to find a valid and not expired :php:`AccessGrant` item for the specific
    :php:`RouteAccessSubject('/my/route')` aspect in the current backend user session data.
#.  In case no :php:`AccessGrant` can be determined, a new :php:`AccessClaim` is created
    for the specific :php:`RouteAccessSubject` instance and temporarily persisted in the
    current user session data - the claim also contains the originally requested route
    as :php:`ServerRequestInstruction` (a simplified representation of a :php:`ServerRequestInterface`).
#.  Next, the user is redirected to the user interface for providing either their own password, or
    the global install tool password as alternative.
#.  Given, the password was correct, the :php:`AccessClaim` is "converted" to an
    :php:`AccessGrant`, which is only valid for the specific subject (URI `/my/route`)
    and for a limited lifetime.


Configuration
-------------

In general, the configuration for a particular route or module looks like this:

.. code-block:: php

    <?php
    // ...
    'sudoMode' => [
        'group' => 'individual-group-name',
        'lifetime' => AccessLifetime::veryShort,
    ],

* `group` (optional): if given, grants access to other objects of the same `group`
  without having to verify sudo mode again for a the given lifetime. Example:
  Admin Tool modules :guilabel:`Maintainance` and :guilabel:`Settings` are configured with the same
  `systemMaintainer` group - having access to one (after sudo mode verification)
  grants access to the other automatically.
* `lifetime`: enum value of :php:`\TYPO3\CMS\Backend\Security\SudoMode\Access\AccessLifetime`,
  defining the lifetime of a sudo mode verification, afterwards users have to go through
  the process again - cases are `veryShort` (5 minutes), `short` (10 minutes),
  `medium` (15 minutes), `long` (30 minutes), `veryLong` (60 minutes)


For backend routes declared via :file:`Configuration/Backend/Routes.php`, the
relevant configuration would look like this:

.. code-block:: php

    <?php
    return [
        'my-route' => [
            'path' => '/my/route',
            'target' => MyHandler::class . '::process',
            'sudoMode' => [
                'group' => 'mySudoModeGroup',
                'lifetime' => AccessLifetime::short,
            ],
        ],
    ];


For backend modules declared via :file:`Configuration/Backend/Modules.php`, the
relevant configuration would look like this:

.. code-block:: php

    <?php
    return [
        'tools_ExtensionmanagerExtensionmanager' => [
            // ...
            'routeOptions' => [
                'sudoMode' => [
                    'group' => 'systemMaintainer',
                    'lifetime' => AccessLifetime::medium,
                ],
            ],
        ],
    ];


.. index:: Backend, ext:backend
