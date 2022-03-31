.. include:: /Includes.rst.txt

========================================
Breaking: #91066 - Removed ButtonUtility
========================================

See :issue:`91066`

Description
===========

The :php:`ButtonUtility` was superfluous and therefor removed.


Impact
======

You need to remove the usage of the :php:`ButtonUtility` class, otherwise you
will get fatal errors of missing classes.


Affected Installations
======================

All 3rd party extensions that created own widget types with the option to add
a button using the :php:`ButtonUtility::generateButtonConfig()` method are
affected.


Migration
=========

First of all you need to change one line in your Widget class. When assigning
your button parameter to your Fluid Template, you most probably have the following
line:

.. code-block:: php

   'button' => ButtonUtility::generateButtonConfig($this->buttonProvider),

You have to change that into:

.. code-block:: php

   'button' => $this->buttonProvider,

Because you change the variable passed to your template, you also need to do a
small change in your template.

In your template in the footer section, you will find a line like this:

.. code-block:: html

   <a href="{button.link}" target="{button.target}" class="widget-cta">{f:translate(id: button.text, default: button.text)}</a>

You need to change the text property to the title property. So the line above will
become:

.. code-block:: html

   <a href="{button.link}" target="{button.target}" class="widget-cta">{f:translate(id: button.title, default: button.title)}</a>

.. index:: Backend, ext:dashboard, FullyScanned
