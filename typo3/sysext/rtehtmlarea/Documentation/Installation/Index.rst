.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _installation:

Installing the RTE
------------------

The extension is a system extension and is installed by default when
TYPO3 is installed.

Use the Extension Manager to un-install/re-install the extension.

The extension may be installed as a system, global or local extension.

You may be requested to uninstall the following extension: rte\_conf.

If you plan to use the spell checking feature, you should install
extension Static Info Tables (static\_info\_tables). The spell checker
feature requires `GNU Aspell 0.60+ <http://aspell.net/>`_ to be
installed on the server.

Custom elements presented by the User Elements feature may be
maintained with extension `Custom Tags
<http://typo3.org/extensions/repository/search/de_custom_tags/>`_
(extension key: de\_custom\_tags).

Note that the installation dialog will request to create table
tx\_rtehtmlarea\_acronym; this table is used by theAcronym feature.

Upon installation directory uploads/tx\_rtehtmlarea will be created.
Personal dictionaries are stored in subdirectories of this directory.

Upon installation, if RTE has not yet been enabled with the TYPO3
Install tool, it will be automatically enabled:

::

   $TYPO3_CONF_VARS['BE']['RTEenabled'] = 1;


