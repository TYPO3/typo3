.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

This extension is part of the TYPO3 Core, but not installed by default.

..  contents:: Table of contents
    :local:

.. _installation_composer:

Installation with Composer
==========================

Check whether you are already using the extension with:

..  code-block:: bash

    composer show | grep impexp

This should either give you no result or something similar to:

..  code-block:: none

    typo3/cms-impexp       v12.4.11

If it is not installed yet, use the ``composer require`` command to install
the extension:

..  code-block:: bash

    composer require typo3/cms-impexp

The given version depends on the version of the TYPO3 Core you are using.

.. _installation_legacy:

Installation without Composer
=============================

In an installation without Composer, the extension is already shipped but might
not be activated yet. Activate it as follows:

#.  In the backend, navigate to the :guilabel:`Admin Tools > Extensions`
    module.
#.  Click the :guilabel:`Activate` icon for the Import / Export extension.

..  figure:: /Images/ManualScreenshots/InstallActivate.png
    :class: with-border
    :alt: Extension manager showing Import / Export extension

    Extension manager showing Import / Export extension
