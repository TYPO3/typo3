:navigation-title: Custom linktypes

..  include:: /Includes.rst.txt
..  _linktype-implementation:

==========================================================
Implementation of a custom linktype for the link validator
==========================================================

The LinkValidator uses so called "linktypes" to check for different types of
links, for example internal or external links.

All "linktypes" have to implement the
:php:`\TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface`.

Classes implementing the :php-short:`\TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface` are automatically
registered, if :ref:`autoconfigure <t3coreapi:dependency-injection-autoconfigure>`
is enabled in :file:`packages/my_extension/Configuration/Services.yaml`.

Alternatively, one can manually tag a custom link type with the
:yaml:`linkvalidator.linktype` tag:

..  literalinclude:: _Services.yaml
    :caption: packages/my_extension/Configuration/Services.yaml

Due to the autoconfiguration, the identifier has to be provided by the
class directly, using the method :php:`getIdentifier()`.

When extending :php:`\TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype`
it is sufficient to set the `$identifier` class property.

For custom naming of a linktype, the additional interface
:php:`\TYPO3\CMS\Linkvalidator\Linktype\LabelledLinktypeInterface`.
can be implemented, which is also part of the default
:php-short:`\TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype` implementation.

The method :php:`LabelledLinktypeInterface->getReadableName()` is used to
return the custom localized name of a linktype.

..  _linktype-implementation-example:

Example
=======

..  _linktype-implementation-example-add-link-type:

Add new linktype
----------------

You can find the following example in the extension
`t3docs/examples <https://github.com/TYPO3-Documentation/t3docs-examples>`__.

Extend :php:`\TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype` to create
a custom linktype:

..  include:: /CodeSnippets/Examples/ExampleLinkType.rst.txt

Activate the new linktype in the page tsconfig:

..  include:: /CodeSnippets/Examples/ActivateCustomLinktypeTsConfig.rst.txt

The extension that provides the linktype must have a
:file:`Configuration/Services.yaml` file that contains either:

..  literalinclude:: _Services-Autoconfigure.yaml
    :caption: packages/my_extension/Configuration/Services.yaml

Or if autoconfiguration is not desired for some reason:

..  literalinclude:: _Services-Linktype.yaml
    :caption: packages/my_extension/Configuration/Services.yaml

..  _linktype-implementation-override-external:

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
