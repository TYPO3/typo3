.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

Target group: **Administrators**

This extension is part of the TYPO3 Core, but not installed by default.

..  contents:: Table of contents
    :local:

Installation with Composer
==========================

Check whether you are already using the extension with:

..  code-block:: bash

    composer show | grep recycler

This should either give you no result or something similar to:

..  code-block:: none

    typo3/cms-recycler v12.4.5

If it is not installed yet, use the :bash:`composer require` command to install
the extension:

..  code-block:: bash

    composer require typo3/cms-recycler

The given version depends on the version of the TYPO3 Core you are using.

Installation without Composer
=============================

In an installation without Composer, the extension is already shipped, but might
not be activated yet. Activate it as follows:

#.  In the backend, navigate to the :guilabel:`Admin Tools > Extensions`
    module.
#.  Click the :guilabel:`Activate` icon for the Recycler extension.

..  figure:: /Images/InstallActivate.png
    :class: with-border
    :alt: Extension manager showing the Recycler extension

    Activation of the Recycler extension

Next steps
==========

Configure the Recycler module to your needs via
:ref:`user TSconfig <configuration>`.
