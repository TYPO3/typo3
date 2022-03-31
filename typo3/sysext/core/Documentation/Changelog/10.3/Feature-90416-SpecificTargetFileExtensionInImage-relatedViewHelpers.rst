.. include:: /Includes.rst.txt

=============================================================================
Feature: #90416 - Specific target file extension in image-related ViewHelpers
=============================================================================

See :issue:`90416`

Description
===========

TYPO3 core's shipped Fluid ViewHelpers now allow to optionally
specify a target file extension via the new attribute `fileExtension`.

This affects the following ViewHelpers:

- :html:`<f:image>`
- :html:`<f:media>`
- :html:`<f:uri.image>`

This is rather important for specific scenarios where a :html:`<picture>` tag with multiple images are requested, allowing
to e.g. customize rendering for `webp` support, if the servers' ImageMagick version supports `webp` conversion.

In other regard, this might become useful to specify the output
for preview images of `pdf` files which can be converted via `GhostScript` if installed.


Impact
======

TYPO3 Integrators can now use the additional attribute
in their custom Fluid Templates for specific use cases.

Example:

.. code-block:: html

   <picture>
     <source srcset="{f:uri.image(image: fileObject, treatIdAsReference: true, fileExtension: 'webp')}" type="image/webp">
     <source srcset="{f:uri.image(image: fileObject, treatIdAsReference: true, fileExtension: 'jpg')}" type="image/jpeg">
     <f:image image="{fileObject}" treatIdAsReference="true" alt="{fileObject.alternative}" />
   </picture>

.. index:: Fluid, ext:fluid
