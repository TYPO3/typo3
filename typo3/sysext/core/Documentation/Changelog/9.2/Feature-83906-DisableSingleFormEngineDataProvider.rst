.. include:: ../../Includes.txt

=========================================================
Feature: #83906 - Disable single FormEngine data provider
=========================================================

See :issue:`83906`

Description
===========

Single data providers used in the FormEngine data compilation step can be disabled.

As an example, if editing a full database record, the default TcaCheckboxItems could be shut down by setting
:php:`disabled` in the :php:`tcaDatabaseRecord` group in an extensions :file:`ext_localconf.php` file:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord']
    [\TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class]['disabled'] = true;

Extension authors can then add an own data provider which :php:`depends` on the disabled one and is :php:`before` of the
next one to effectively substitute single providers with own solutions if needed.


Impact
======

The disable feature allows extension authors to easily substitute existing data providers with own solutions and avoids
nasty array- and dependency munging by extension authors.

.. index:: Backend, PHP-API