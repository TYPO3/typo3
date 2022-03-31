.. include:: /Includes.rst.txt

==============================================================================
Important: #93398 - Possibility to ignore submitted values in HiddenViewHelper
==============================================================================

See :issue:`93398`

Description
===========

A new argument :php:`respectSubmittedDataValue` is added to Fluid's
:php:`HiddenViewHelper` view helper. It allows to enable or disable the usage of
previously submitted values for the corresponding field. This is especially
useful if dealing with sub requests, e.g. when a :php:`\TYPO3\CMS\Extbase\Http\ForwardResponse` is
being dispatched within Extbase.

Example
=======

.. code-block:: html

   <f:form.hidden property="hiddenProperty" value="{form.hiddenProperty}" respectSubmittedDataValue="false"/>

.. index:: Fluid, ext:fluid
