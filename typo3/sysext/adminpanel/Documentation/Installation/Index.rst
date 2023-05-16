.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

This extension is part of the TYPO3 Core, but not installed by default.

..  contents:: Table of contents
    :local:

Installation with Composer
==========================

Check whether you are already using the extension with:

..  code-block:: bash

    composer show | grep adminpanel

This should either give you no result or something similar to:

..  code-block:: none

    typo3/cms-adminpanel v12.4.1

If it is not installed yet, use the ``composer require`` command to install
the extension:

..  code-block:: bash

    composer require typo3/cms-adminpanel

The given version depends on the version of the TYPO3 Core you are using.

Installation without Composer
=============================

In an installation without Composer, the extension is already shipped but might
not be activated yet. Activate it as follows:

#.  In the backend, navigate to the :guilabel:`Admin Tools > Extensions`
    module.
#.  Click the :guilabel:`Activate` icon for the Admin Panel extension.

..  figure:: ../Images/InstallActivate.png
    :class: with-border
    :alt: Extension manager showing Admin Panel extension

    Extension manager showing Admin Panel extension

Next steps
==========

Configure the Admin Panel to be displayed to logged-in backend admins the
TypoScript configuration :t3-typoscript:`config.admPanel = 1 <config.admPanel>`.

By default the admin panel is displayed to logged-in admins only. This behaviour
can be changed by setting :t3-user-tsconfig:`admPanel.enable` for certain
backend users or groups.
