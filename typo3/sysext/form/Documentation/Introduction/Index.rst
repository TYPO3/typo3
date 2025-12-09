.. include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  note::

    This documentation will be extended on a constant basis. If you have
    problems understanding a certain aspect or if you notice something
    missing, please contribute to improve it. This will help you as well as everyone else!

    Get in touch with us:

    *   Find us on `Slack <https://typo3.community/meet/slack>`_ and join the
        channel `#ext:form`.
    *   Use the "Edit on Github" function.


..  _what-does-it-do:

What does it do?
================

The :composer:`typo3/cms-form` extension is a system extension that provides a
flexible, extendable, and easy-to-use form framework. It incorporates interfaces
and functionality that allow editors, integrators and developers to build forms.

Non-technical editors can use the :guilabel:`Web > Forms` backend module.
They can create and manage forms using a simple drag and drop interface.
Forms can be previewed instantly.

Experienced integrators can build ambitious forms which are
stored in a site package. These forms can use powerful finishers and ship
localization files.

Developers can use the PHP API to create interfaces with conditional
form elements, register new validators and finishers, as well as create
custom form elements. Plenty of hooks allow form creation
and processing to be manipulated.

..  figure:: Images/introduction_form_editor.png
    :alt: The form creation wizard

    Form editor displaying a new form in the abstract view

..  _features_list:

Features list
=============

Here are some of the features of the form framework:

*   form editor

    *   fully customizable editor for building complex forms
    *   replaceable and extendable form editor components
    *   JS API to extend form editor

*   PHP API

    *   entire forms via API
    *   own renderers for form and/ or form elements
    *   conditional steps, form elements and validators based on other form
        elements

*   configuration

    *   YAML as configuration and definition language including inheritance
        and overrides
    *   file based
    *   behaviour and design of the frontend, plugin, and form editor can be
        adapted for individual forms
    *   'prototypes' can be used as boilerplate

*   form elements

    *   own form elements possible
    *   uploads handled as FAL objects

*   finishers

    *   ships built-in finishers, like email, redirect, and save-to-database
    *   own finishers possible
    *   finisher configuration can be overridden in the form plugin

*   validators

    *   own validators possible

*   miscellaneous

    *   multiple languages support
    *   multiple steps support
    *   multiple forms on one page
    *   built-in spam protection (honeypot)
