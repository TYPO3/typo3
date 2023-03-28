.. include:: /Includes.rst.txt

.. _feature-100293-1679673289:

================================================================
Feature: #100293 - New ContentObject EXTBASEPLUGIN in TypoScript
================================================================

See :issue:`100293`

Description
===========

In order to lower the barrier for newcomers in the TYPO3 world, TYPO3 now has
a custom ContentObject in TypoScript called :typoscript:`EXTBASEPLUGIN`.

Previously, TypoScript code for Extbase plugins looked like this:

..  code-block:: typoscript

    page.10 = USER
    page.10 {
        userFunc = TYPO3\\CMS\\Extbase\\Core\\Bootstrap->run
        extensionName = shop
        pluginName = cart
    }

The new way, which Extbase plugin registration uses under the hood now, looks
like this:

..  code-block:: typoscript

    page.10 = EXTBASEPLUGIN
    page.10.extensionName = shop
    page.10.pluginName = cart

The old way still works, but it is recommended to use the :typoscript:`EXTBASEPLUGIN`
ContentObject, as the direct reference to a PHP class (Bootstrap) might be
optimized in future versions.


Impact
======

This change is an effort to distinguish between plugins and regular other
more static content.

Extbase is the de-facto standard for plugins, which serve dynamic content by
custom PHP code divided in controllers and actions by extension developers.

Regular other content can be written in pure TypoScript, such as ContentObjects
like FLUIDTEMPLATE, HMENU, COA or TEXT is used for other kind of renderings
in the frontend.

.. index:: TypoScript, ext:extbase
