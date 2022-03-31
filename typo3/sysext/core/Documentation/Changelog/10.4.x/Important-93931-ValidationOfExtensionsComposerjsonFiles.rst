.. include:: /Includes.rst.txt

=================================================================
Important: #93931 - Validation of Extensions' composer.json files
=================================================================

See :issue:`93931`

Description
===========

Future TYPO3 versions will require extensions to have a valid
:file:`composer.json` file as a replacement for :file:`ext_emconf.php`.
This description file is used to define dependencies and the
loading order of extensions within TYPO3.

In order to support site administrators by creating valid
:file:`composer.json` files for their extensions, the Extension manager
now lists all affected extensions with details about the necessary
adaptations. Site administrators can also use the new proposal
functionality, which suggests a possible and valid :file:`composer.json`
file for those extensions by accessing TYPO3.org (TER). TYPO3.org
is used to resolve dependencies to extensions, available in the TER.

You can also check your current installation for such extensions
in the reports module.

Further information on the transition phase and examples
of valid :file:`composer.json` files for TYPO3 Extensions can be found on
https://extensions.typo3.org/help/composer-support

.. index:: Backend, ext:extensionmanager
