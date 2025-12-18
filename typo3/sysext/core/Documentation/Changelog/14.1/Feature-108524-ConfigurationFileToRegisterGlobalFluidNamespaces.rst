..  include:: /Includes.rst.txt

..  _feature-108524-1766073747:

=========================================================================
Feature: #108524 - Configuration file to register global Fluid namespaces
=========================================================================

See :issue:`108524`

Description
===========

The extension-level configuration file `Configuration/Fluid/Namespaces.php`
is introduced, which enables a structured way to register and extend global
Fluid namespaces. This replaces the old configuration in
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']`, see
:ref:`deprecation <deprecation-108524-1766073657>`.

Example:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Fluid/Namespaces.php

    <?php

    return [
        'myext' => ['MyVendor\\MyExtension\\ViewHelpers'],
        'mycmp' => ['MyVendor\\MyExtension\\Components'],
    ];

Overriding existing ViewHelpers
-------------------------------

TYPO3 reads and merges `Configuration/Fluid/Namespaces.php` files from all
loaded extensions in the usual loading order, which can be manipulated by
declaring dependencies in `composer.json` and possibly `ext_emconf.php`. If an
extension registers a namespace that has already been registered by another
extension, these namespaces will be merged by Fluid. This allows extensions
to override ViewHelpers of another extension selectively.

Example (extension2 depends on extension1):

..  code-block:: php
    :caption: EXT:my_extension1/Configuration/Fluid/Namespaces.php

    <?php

    return [
        'myext' => ['MyVendor\\MyExtension1\\ViewHelpers'],
    ];

..  code-block:: php
    :caption: EXT:my_extension2/Configuration/Fluid/Namespaces.php

    <?php

    return [
        'myext' => ['MyVendor\\MyExtension2\\ViewHelpers'],
    ];

Resulting namespace definition:

..  code-block:: php

    [
        'myext' => [
            'MyVendor\\MyExtension1\\ViewHelpers',
            'MyVendor\\MyExtension2\\ViewHelpers',
        ],
    ];

Namespaces are processed in reverse order, which means that
:html:`<myext:demo />` would first check for
`EXT:my_extension2/Classes/ViewHelpers/DemoViewHelper.php`, and would
fall back to `EXT:my_extension1/Classes/ViewHelpers/DemoViewHelper.php`.

PSR-14 event to modify namespaces
---------------------------------

The new :php-short:`\TYPO3\CMS\Fluid\Event\ModifyNamespacesEvent` is
introduced, which allows modification of the whole namespaces array
before it is being passed to Fluid. This allows for example to:

* completely redefine an existing namespace (instead of extending it)
* add namespaces conditionally
* modify order of merged namespaces

Example:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ModifyNamespacesListener.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Fluid\Event\ModifyNamespacesEvent;

    #[AsEventListener]
    final readonly class ModifyNamespacesListener
    {
        public function __invoke(ModifyNamespacesEvent $event): void
        {
            $namespaces = $event->getNamespaces();
            // Replace existing "theme" namespace completely
            $namespaces['theme'] = ['MyVendor\\MyExtension\\ViewHelpers'];
            $event->setNamespaces($namespaces);
        }
    }

Note that namespaces might still be imported locally from within a
template file, which is unaffected by this event.

Backwards-compatible namespaces in extensions
---------------------------------------------

There are several ways to provide backwards-compatible global namespaces
in extensions, depending on the concrete use case:

*   preferred: Define namespace in `TYPO3_CONF_VARS` (with version check) and
    in new `Namespaces.php`. This means that in TYPO3 v14 installations the new
    `Namespaces.php` can already be used to extend the namespace.
*   alternative: Define namespace both in `TYPO3_CONF_VARS` (without version
    check) and in new `Namespaces.php`. This means however that the namespace
    can only be extended with `TYPO3_CONF_VARS`, not with the new `Namespaces.php`.
*   keep `TYPO3_CONF_VARS` until support for < v14 is dropped by the extension.
    This can only be extended with `TYPO3_CONF_VARS` as well.
*   implement own merging logic in :php:`ModifyNamespacesEvent` if necessary.

Example for preferred option:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Fluid/Namespaces.php

    <?php

    return [
        'myext' => ['MyVendor\\MyExtension\\ViewHelpers'],
    ];

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    <?php

    if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 14) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fluid']['namespaces']['myext'][] = 'MyVendor\\MyExtension\\ViewHelpers';
    }

To sum up:

* `Namespaces.php` can only extend namespaces defined in `Namespaces.php`.
* `TYPO3_CONF_VARS` can extend both `TYPO3_CONF_VARS` and `Namespaces.php`.
* :php:`ModifyNamespacesEvent` can modify everything.

Impact
======

Extensions can now register global Fluid namespaces in a dedicated
configuration file `Configuration/Fluid/Namespaces.php`. The old
`TYPO3_CONF_VARS` registration can be used for backwards compatibility.

..  index:: Fluid, ext:fluid
