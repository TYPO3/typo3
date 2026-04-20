.. include:: /Includes.rst.txt

.. _feature-102215-1709554850:

===========================================================================
Feature: #102215 - ViewHelper and data structure to render srcset attribute
===========================================================================

See :issue:`102215`

Description
===========

The `srcset` HTML attribute can be used to provide different image sizes to
the browser. The browser is free to choose which image size to use, which is
why the images must all be scaled versions of the same original image. Each
image in the `srcset` list also has a descriptor which either specifies
the absolute width of the image, for example `400w`, or is a scale factor
relative to the original image size for use on high-density screens, for
example `2x`.

`srcset` attributes are used by various HTML tags:

*   :html:`<img srcset="image@500.jpg 500w, image@1000.jpg 1000w" />`
*   :html:`<source srcset="image@1x.jpg 1x, image@2x.jpg 2x" />` inside
    :html:`<picture>`
*   :html:`<link rel="preload" as="image" imagesrcset="image@500.jpg 500w, image@1000.jpg 1000w" />`

..  note::

    File name notation like `image@500.jpg` is just a regular file name
    used to indicate its pixel dimensions. The `@` notation has no inherent
    conversion magic, unlike the descriptors `500w` and `1x`.

To generate `srcset` attributes easily based on input, a new
data structure has been added to calculate the appropriate image sizes from a
list of descriptors. Based on these calculations, image files can be
generated using the image manipulation API.

..  code-block:: php
    :caption: EXT:my_ext/Classes/Service/SomeImageService.php

    use TYPO3\CMS\Core\Html\Srcset\SrcsetAttribute;

    // From width descriptors
    $srcset = SrcsetAttribute::createFromDescriptors(['400w', '600w', '800w']);

    // Or from pixel density descriptors (a reference width must be supplied)
    $srcset = SrcsetAttribute::createFromDescriptors(
        ['1.5x', '2x', '3x'],
        800
    );

    // Add image URIs
    foreach ($srcset->getCandidates() as $candidate) {
        // Generate scaled image here using $candidate->getCalculatedWidth()

        // Set URI of the generated image
        $candidate->setUri($generatedImageUri);
    }

    // Render srcset attribute
    $srcsetString = $srcset->generateSrcset();

To generate `srcset` attributes in Fluid templates, a new ViewHelper has also
been introduced.

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
that used :fluid:`f:uri.image` for each image size. This now makes it easier
to provide images in different dimensions based on a single image.

.. index:: Fluid, PHP-API, ext:core, ext:fluid
