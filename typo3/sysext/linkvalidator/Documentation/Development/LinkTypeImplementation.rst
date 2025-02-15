.. include:: /Includes.rst.txt

.. _linktype-implementation:

================
Custom linktypes
================

The LinkValidator uses so called "linktypes" to check for different types of
links, for example internal or external links.

All "linktypes" have to implement the
:ref:`\TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface <linkvalidatorapi-LinktypeInterface>`.

Classes implementing the :php:`LinktypeInterface` are automatically
registered, if :ref:`autoconfigure <t3coreapi:dependency-injection-autoconfigure>`
is enabled in :file:`Services.yaml`.

Alternatively, one can manually tag a custom link type with the
:yaml:`linkvalidator.linktype` tag:

..  literalinclude:: _Services.yaml
    :caption: packages/my_extension/Configuration/Services.yaml

Due to the autoconfiguration, the identifier has to be provided by the
class directly, using the method :php:`getIdentifier()`.

When extending :ref:`\TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype <linkvalidatorapi-AbstractLinktype>`
it is sufficient to set the :php:`$identifier` class property.

Example
=======

Add new linktype
----------------

You can find the following example in the extension
`t3docs/examples <https://github.com/TYPO3-Documentation/t3docs-examples>`__.

Extend :ref:`\TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype <linkvalidatorapi-AbstractLinktype>` to create
a custom linktype:

.. include:: /CodeSnippets/Examples/ExampleLinkType.rst.txt

Activate the new linktype in the page tsconfig:

.. include:: /CodeSnippets/Examples/ActivateCustomLinktypeTsConfig.rst.txt

The extension that provides the linktype must have a
:file:`Configuration/Services.yaml` file that contains either:

..  literalinclude:: _Services-Autoconfigure.yaml
    :caption: packages/my_extension/Configuration/Services.yaml

Or if autoconfiguration is not desired for some reason:

..  literalinclude:: _Services-Linktype.yaml
    :caption: packages/my_extension/Configuration/Services.yaml

.. _linktype-implementation-override-external:

Override the ExternalLinktype class
-----------------------------------

A new custom class should replace
:php:`\TYPO3\CMS\Linkvalidator\Linktype\ExternalLinktype`. The class inherits
existing functionality from :php:`ExternalLinktype`, but will be registered with
the identifier "custom_external":

..  literalinclude:: _ExternalLinktype.php.inc
    :language: php
    :caption: packages/my_extension/Classes/Linktype/ExternalLinktype.php

Use the new linktype:

..  code-block:: typoscript
    :caption: packages/my_extension/Configuration/page.tsconfig

    mod.linkvalidator.linktypes = db,file,custom_external

Since the identifier changes, the configuration should be copied to
:typoscript:`mod.linkvalidator.linktypesConfig.custom_external`, so that it will be
passed to the linktype, for example:

..  code-block:: typoscript
    :caption: packages/my_extension/Configuration/page.tsconfig

    mod.linkvalidator.linktypesConfig.custom_external < mod.linkvalidator.linktypesConfig.external

Migration from TYPO3 11 LTS and below
=====================================

Remove :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']`
from your :file:`ext_localconf.php` file.

If :yaml:`autoconfigure` is not enabled in your :file:`Configuration/Services.(yaml|php)`,
add the tag :yaml:`linkvalidator.linktype` manually to your `linktype` service.

Additionally, make sure to either implement
:php:`public function getIdentifier(): string` or, in case your `linktype` extends
:php:`AbstractLinktype`, to set the `$identifier` class property.
