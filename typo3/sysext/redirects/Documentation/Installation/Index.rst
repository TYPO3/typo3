.. include:: /Includes.rst.txt

..  _installation:

============
Installation
============

This extension is part of the TYPO3 Core, but not installed by default.

..  contents:: Table of contents
    :local:

..  _installation-composer:

Installation with Composer
==========================

Check whether you are already using the extension with:

..  code-block:: bash

    composer show | grep redirects

This should either give you no result or something similar to:

..  code-block:: none

    typo3/cms-redirects       v12.4.11

If it is not installed yet, use the ``composer require`` command to install
the extension:

..  code-block:: bash

    composer require typo3/cms-redirects

The given version depends on the version of the TYPO3 Core you are using.

..  _installation-non-composer:

Installation without Composer
=============================

In an installation without Composer, the extension is already shipped but might
not be activated yet. Activate it as follows:

#.  In the backend, navigate to the :guilabel:`System > Extensions`
    module.
#.  Click the :guilabel:`Activate` icon for the Redirects extension.

..  figure:: /Images/InstallActivate.png
    :class: with-border
    :alt: Extension manager showing Redirects extension

    Extension manager showing Redirects extension
