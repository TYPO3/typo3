.. include:: /Includes.rst.txt

.. _breaking-96222:

======================================================
Breaking: #96222 - Add getOptions() to WidgetInterface
======================================================

See :issue:`96222`

Description
===========

With :issue:`93210` the dashboard was extended for the functionality to
refresh single widgets. This also required to extend the :php:`WidgetInterface`.
To stick to TYPO3's backwards compatibility promise, the new method was
commented out and instead a :php:`methodExists()` check performed.

This now has changed. The check is removed and the :php:`WidgetInterface`
now forces the presence of the :php:`getOptions()` method in all widgets.

Impact
======

All dashboard widgets are now forced to implement the :php:`getOptions()`
method, returning the widget options. Otherwise this will cause a PHP
fatal error.

Affected Installations
======================

All installations using custom dashboard widgets.

Migration
=========

Add the :php:`getOptions()` method to all of your custom widget classes.

..  code-block:: php

    public function getOptions(): array
    {
        return $this->options;
    }

.. index:: Backend, PHP-API, NotScanned, ext:dashboard
