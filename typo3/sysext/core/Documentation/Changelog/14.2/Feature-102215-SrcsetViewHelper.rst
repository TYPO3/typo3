.. include:: /Includes.rst.txt

.. _feature-102215-1709554850:

===========================================================================
Feature: #102215 - ViewHelper and data structure to render srcset attribute
===========================================================================

See :issue:`102215`

Description
===========

The `srcset` HTML attribute can be used to provide different image sizes to
the browser. The browser is free to choose which image size will be used,
which is why the images must all be scaled variants of the same original
image. Each image in the srcset-list also has a so-called descriptor, either
specifying the absolute width of the image (e. g. `400w`) or a scale factor
relative to the original image size to be used on high density screens
(e. g. `2x`).

Srcset attributes are used by various HTML tags:

* :html:`<img srcset="image@500.jpg 500w, image@1000.jpg 1000w" />`
* :html:`<source srcset="image@1x.jpg 1x, image@2x.jpg 2x" />` inside  :html:`<picture>`
* :html:`<link rel="preload" as="image" imagesrcset="image@500.jpg 500w, image@1000.jpg 1000w" />`

..  note::

    The file name notation like `image@500.jpg` is just a regular file name
    to indicate its pixel dimensions. The `@` notation has no inherent conversion
    magic, unlike the operators `500w` and `1x`.

To generate `srcset` attributes based on various inputs more easily, a new data
structure is added to calculate the appropriate image sizes based on a list of
supplied descriptors. Based on these calculations, image files can be generated
using the existing image manipulation API.

..  code-block:: php
    :caption: EXT:my_ext/Classes/Service/SomeImageService.php

    use TYPO3\CMS\Core\Html\Srcset\SrcsetAttribute;

    // from width descriptors
    $srcset = SrcsetAttribute::createFromDescriptors(['400w', '600w', '800w']);

    // or from pixel density descriptors (reference width needs to be supplied)
    $srcset = SrcsetAttribute::createFromDescriptors(['1.5x', '2x', '3x'], 800);

    // Add image URIs
    foreach ($srcset->getCandidates() as $candidate) {
        // Generate scaled image here using $candidate->getCalculatedWidth()

        // Set URI of generated image
        $candidate->setUri($generatedImageUri);
    }

    // Render srcset attribute
    $srcsetString = $srcset->generateSrcset();

To be able to generate `srcset` attributes in Fluid templates, a new ViewHelper
has been introduced.

..  code-block:: html
    <picture>
        <source
            srcset="{f:image.srcset(image: imageObject, srcset: '400w, 600w, 800w', cropVariant: 'wide')}"
            sizes="100vw"
            media="(min-width: 1200px)"
        />
        <!-- ... -->
    </picture>

Impact
======

The new ViewHelper `f:image.srcset` simplifies previous manual implementations
using :fluid:`f:uri.image` for each image size manually. This now offers
to easily supply images at different dimensions, based on one image.

.. index:: Fluid, PHP-API, ext:core, ext:fluid
