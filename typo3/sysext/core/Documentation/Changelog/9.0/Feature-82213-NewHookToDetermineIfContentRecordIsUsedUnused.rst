.. include:: ../../Includes.txt

========================================================================
Feature: #82213 - New hook to determine if content record is used/unused
========================================================================

See :issue:`82213`

Description
===========

A new hook has been added to the :php:`PageLayoutView` class, determining whether a
content record is used or not. The hook allows third party code to change the
:php:`$used` parameter by returning a boolean, thus changing which content records
are shown in the "unused content elements" section of the backend page module.


Impact
======

Without providing an own hook, content elements with an colPos not defined within
the current backend layout are marked as unused. You have to register and provide
an own PHP class checking if an content record is used within your configuration.

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['record_is_used']['myExt'] =
    MyExt\MyExt\Hooks\PageLayoutViewHook::class . '->contentIsUsed';

.. code-block:: php

    namespace MyExt\MyExt\Hooks;

    use TYPO3\CMS\Backend\View\PageLayoutView;

    class PageLayoutViewHook
    {
        public function contentIsUsed(array $params, PageLayoutView $parentObject): bool
        {
            if ($params['used']) {
               return true;
            }
            $record = $params['record'];
            return $record['colPos'] === 999 && !empty($record['tx_myext_content_parent']);
        }
    }

.. index:: Backend, PHP-API
