.. include:: /Includes.rst.txt

.. _feature-101391-1689772689:

==========================================================
Feature: #101391 - Add base64 attribute to ImageViewHelper
==========================================================

See :issue:`101391`

Description
===========

The ViewHelpers :ref:`<f:image> <t3viewhelper:typo3-fluid-image>` and
:ref:`<f:uri.image> <t3viewhelper:typo3-fluid-uri-image>` now
support the attribute :fluid:`base64="true"` that will provide
a possibility to return the value of the image's :fluid:`src` attribute
encoded in base64.

..  code-block:: html

    <f:image base64="true" src="EXT:backend/Resources/Public/Images/typo3_logo_orange.svg" height="20" class="pr-2" />
    <img src="{f:uri.image(base64: 'true', src:'EXT:backend/Resources/Public/Images/typo3_logo_orange.svg')}">

Will result in the according HTML tag providing the image encoded in base64.

.. code-block:: html

    <img class="pr-2" src="data:image/svg+xml;base64,PHN2...cuODQ4LTYuNzU3Ii8+Cjwvc3ZnPgo=" alt="" width="20" height="20">
    <img src="data:image/svg+xml;base64,PHN2...cuODQ4LTYuNzU3Ii8+Cjwvc3ZnPgo=">

This can be particularly useful inside `\TYPO3\CMS\Core\Mail\FluidEmail` or
to prevent unneeded HTTP calls.

.. index:: Fluid, ext:fluid
