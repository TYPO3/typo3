.. include:: /Includes.rst.txt

.. _linktype-implementation:

================
Custom linktypes
================

The LinkValidator uses so called "linktypes" to check for different types of
links, for example internal or external links.

All "linktypes" have to implement the
:php:interface:`TYPO3\\CMS\\Linkvalidator\\Linktype\\LinktypeInterface`.

Classes implementing the :php:`LinktypeInterface` are automatically
registered, if :ref:`autoconfigure <t3coreapi:dependency-injection-autoconfigure>`
is enabled in :file:`Services.yaml`.

Alternatively, one can manually tag a custom "linktype" with the
:yaml:`linkvalidator.linktype` tag:

.. code-block:: yaml

    Vendor\Extension\Linktype\MyCustomLinktype:
      tags:
        - name: linkvalidator.linktype

Due to the autoconfiguration, the identifier has to be provided by the
class directly, using the method :php:`getIdentifier()`.

When extending :php:class:`TYPO3\\CMS\\Linkvalidator\\Linktype\\AbstractLinktype`
it is sufficient to set the :php:`$identifier` class property.

Example
=======

You can find the following example in the extension
`t3docs/examples <https://github.com/TYPO3-Documentation/t3docs-examples>`__.

Extend :php:class:`TYPO3\\CMS\\Linkvalidator\\Linktype\\AbstractLinktype` to create
a custom linktype:

.. include:: /CodeSnippets/Examples/ExampleLinkType.rst.txt

Activate the new linktype in the page tsconfig:

.. include:: /CodeSnippets/Examples/ActivateCustomLinktypeTsConfig.rst.txt

The extension that provides the linktype must have a
:file:`Configuration/Services.yaml` file that contains either:

.. code-block:: yaml
    :caption: EXT:examples/Configuration/Services.yaml

    services:
       _defaults:
          autoconfigure: true

Or if autoconfiguration is not desired for some reason:

.. code-block:: yaml
    :caption: EXT:examples/Configuration/Services.yaml

    services:
       T3docs\Examples\LinkValidator\LinkType\ExampleLinkType:
          tags:
             -  name: linkvalidator.linktype

Migration from TYPO3 11 LTS and below
=====================================

Remove :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']`
from your :file:`ext_localconf.php` file.

If :yaml:`autoconfigure` is not enabled in your :file:`Configuration/Services.(yaml|php)`,
add the tag :yaml:`linkvalidator.linktype` manually to your `linktype` service.

Additionally, make sure to either implement
:php:`public function getIdentifier(): string` or, in case your `linktype` extends
:php:`AbstractLinktype`, to set the `$identifier` class property.
