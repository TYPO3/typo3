.. include:: ../../Includes.txt

=========================================================
Feature: #77910 - EXT:form - introduce new form framework
=========================================================

See :issue:`77910`

Description
===========

A flexible framework for building forms has been integrated. It replaces the legacy 'form wizard' based on ExtJS and the
depending frontend rendering system.

The new backend 'form editor' relies on vanilla JS and jQuery. Different JS patterns have been applied to ensure
a modern architecture, high flexibility and extensibility.

A new backend module lists all existing forms and allows the creation of new ones. The 'mailform' content element
has been reworked. It lists available forms and enables the backend editor to override certain settings, e.g. 'finisher'
settings (formerly known as 'postProcessors').

Until now it was not possible to customize and extend the 'form editor'. To allow the registration of new
finishers, validators and pre-defined form elements a lot of architectural changes would have been necessary. After a long
conceptional phase the team decided to remove the former code base, backport the 'form' package of the Flow
project and improve the given ideas and concepts. The result is a new form extension. A lot of code received
major improvements and tons of additional features have been integrated.

The list of features is long and impressive. The documentation will explain the ideas, concept and architecture
as well as the functionality in detail. The following list names some of them:

* YAML as configuration and description language including inheritances and overrides.
* File-based configuration.
* All JavaScript components of the form wizard (and the wizard itself) can be replaced or extended.
* Own PHP renderers for form and/or form elements are possible.
* Create entire forms via API.
* Create conditions for form elements and validators programmatically.
* Create 'prototypes' and use them as boilerplate.
* Create new form elements and use them in the wizard.
* Uploads are handled as FAL objects.
* Ships with a bunch of built-in finishers, like email, redirect, save to database.
* Create own finishers. Override those in the content element.
* Create and apply own validators.
* Multi language support.
* Multi step support.
* Multiple forms per page.
* Built-in spam protection (honeypot).


Impact
======

Happy little wizard.

.. index:: Frontend, PHP-API, JavaScript, ext:form, Backend