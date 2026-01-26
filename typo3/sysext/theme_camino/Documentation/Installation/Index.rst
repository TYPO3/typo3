:navigation-title: Installation

..  include:: /Includes.rst.txt
..  _installation:

====================================
Installing the Camino theme manually
====================================

..  include:: /_VersionAdded.rst.txt

When newly installing TYPO3 14.1 in either in Composer or Classic (non-Composer)
mode, the administrator will have the option to install and activate Camino
during the installation process.

For existing installations, the theme will need to be installed. This is done
either via Composer or the System > Extensions` module for Classic mode
installations.

..  contents::

..  _installing-camino-existing-installation-composer:

Composer-based installations: Require "typo3/theme-camino"
==========================================================

Check if the package :composer:`typo3/theme-camino` is already installed:

..  code-block:: bash

    composer show | grep camino

This should either give no result or something similar to:

..  code-block:: none

    typo3/theme-camino       v14.1.0

If it is not installed, use the `composer require` command to install
the extension:

..  code-block:: bash

    composer require typo3/theme-camino

Once the extension has been installed, activate it using the following command:

..  code-block:: bash

    typo3 extension:setup

..  _installing-camino-existing-installation-classic:

Classic mode installations: Activate theme Camino
=================================================

For existing Classic mode TYPO3 installations, activate the extension via the
:guilabel:`System > Extensions` module:

In submodule :guilabel:`Installed Extensions` search for "camino" and activate
the extension. It was installed but not activated during installation / update.

..  figure:: /Images/ActivateExtension.png
    :alt: Screenshot of the "System > Extensions" backend module with camino still disabled
    :zoom: lightbox

    Activate the Camino Theme in the "System > Extensions" backend module
