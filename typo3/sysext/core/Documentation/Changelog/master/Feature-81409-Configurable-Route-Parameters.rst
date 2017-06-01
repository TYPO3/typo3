.. include:: ../../Includes.txt

===============================================
Feature: #81409 - Configurable Route Parameters
===============================================

See :issue:`81409`

Description
===========

Routes definitions are extended by the possibility to defined default parameters.
Those parameters can be overridden during the regular URI generation process.

Several AjaxRoutes inhibited the backend session update to not keep the session
alive by periodic polling. Those `skipSessionUpdate` parameters have been removed
from the specific URI generation invocations and moved to the central route definitions.

Default route parameters are defined in an associative key-value-array using the
index `parameters`. This definition can be used for both, plain routes and AJAX routes.

.. code-block:: php

    'systeminformation_render' => [
        'path' => '/system-information/render',
        'target' => \TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem::class . '::renderMenuAction',
        'parameters' => [
            'skipSessionUpdate' => 1
        ]
    ]

Impact
======

Developers have easier and more standardized control over AjaxRoute parameters.

.. index:: JavaScript, Backend
