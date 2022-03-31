.. include:: /Includes.rst.txt

=====================================================
Feature: #61170 - Add additional hook for record list
=====================================================

See :issue:`61170`


Description
===========

An additional hook has been added to `EXT:recordlist` to render content above any other content.

Example of usage

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/Modules/Recordlist/index.php']['drawHeaderHook']['extkey'] = \Vendor\Extkey\Hooks\PageHook::class . '->render';

.. index:: Backend, LocalConfiguration
