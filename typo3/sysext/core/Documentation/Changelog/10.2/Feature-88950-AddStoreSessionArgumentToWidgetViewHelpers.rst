.. include:: /Includes.rst.txt
.. highlight:: xml

===================================================================
Feature: #88950 - Add "storeSession" argument to Widget ViewHelpers
===================================================================

See :issue:`88950`

Description
===========

Widget ViewHelpers, by default, can store the widgets session in the database by utilizing a cookie.
In frontend context this would automatically create a ``fe_typo_user`` cookie when,
for instance, the :html:`<f:widget.autocomplete>` ViewHelper is used.

As this is not always a desired behaviour (gdpr),
a boolean argument ``storeSession`` has been added to :php:`\TYPO3\CMS\Fluid\Core\Widget\AbstractWidgetViewHelper`,
which defaults to true and can be used to disable session storage for this ViewHelper.

This will automatically create a ``fe_typo_user`` cookie in the frontend::

   <f:widget.autocomplete for="name" objects="{posts}" searchProperty="author" />

This will not create a cookie in frontend::

   <f:widget.autocomplete for="name" objects="{posts}" searchProperty="author" storeSession="false" />

Impact
======

The default value of the property `storeSession` is set to `true`,
so no changes need to be done in existing implementations of Widget ViewHelpers.

.. index:: Fluid, ext:fluid
