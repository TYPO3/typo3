.. include:: ../../Includes.txt

=====================================================
Feature: #61170 - Add additional hook for record list
=====================================================

See :issue:`61170`

Description
===========

An additional hook is added to `EXT:recordlist` to render content above any other content.

Example of usage

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawHeaderHook']['sys_note'] = \Vendor\Extkey\Hooks\PageHook::class . '->render';

.. index:: Backend, NotScanned
