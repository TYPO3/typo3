.. include:: /Includes.rst.txt

============================================================
Breaking: #77987 - Removal fe_users rendering in page module
============================================================

See :issue:`77987`

Description
===========

Because of the deprecation of :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']`,
the records of the table `fe_users` are no longer rendered in the page module.


Affected Installations
======================

All installations.


Migration
=========

By using the following code in the :php:`ext_localconf.php` file, the records can be shown again:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['cms']['db_layout']['addTables']['fe_users'] =
        array (0 => array(
            'MENU' => '',
            'fList' =>  'username,usergroup,name,email,telephone,address,zip,city',
            'icon' => true
        )
    );

.. index:: LocalConfiguration, Backend
