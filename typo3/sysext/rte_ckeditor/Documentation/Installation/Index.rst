.. include:: /Includes.rst.txt


.. _installation:

============
Installation
============

This extension is part of the TYPO3 Core.

..  contents:: Table of contents
    :local:

Installation with Composer
==========================

Check whether you are already using the extension with:

..  code-block:: bash

    composer show | grep rte

This should either give you no result or something similar to:

..  code-block:: none

    typo3/cms-rte-ckeditor       v12.4.11

If it is not installed yet, use the ``composer require`` command to install
the extension:

..  code-block:: bash

    composer require typo3/cms-rte-ckeditor

The given version depends on the version of the TYPO3 Core you are using.

Installation without Composer
=============================

In an installation without Composer, the extension is already shipped but might
not be activated yet. Activate it as follows:

#.  In the backend, navigate to the :guilabel:`Admin Tools > Extensions`
    module.
#.  Click the :guilabel:`Activate` icon for the RTE CKEditor extension.

..  figure:: /Images/InstallActivate.png
    :class: with-border
    :alt: Extension manager showing RTE CKEditor extension

    Extension manager showing RTE CKEditor extension
