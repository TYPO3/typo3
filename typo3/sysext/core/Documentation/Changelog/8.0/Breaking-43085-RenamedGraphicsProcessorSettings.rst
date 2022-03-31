
.. include:: /Includes.rst.txt

===========================================================================
Breaking: #43085 - Change GFX settings prefix im\_* to generic processor\_*
===========================================================================

See :issue:`43085`

Description
===========

Graphics processor settings for Image- or GraphicsMagick have been renamed
in `LocalConfiguration.php`. The former prefix `im\_` has been replaced with
the unified prefix `processor\_`.

Negative namings like `noScaleUp` have been changed positive counterparts.
During the conversion the previous configuration values are negated to reflect
the changes in semantics of these options.

In addition references to specific versions of ImageMagick/GraphicsMagick
have been removed from settings names and values. For a detailed list of
changes please consult the information in the migration section.

The unused configuration option `image\_processing` has been removed without
replacement.

The processor specific configuration option `colorspace` has been namespaced
below the `processor\_` hierarchy.


Impact
======

Existing settings in `LocalConfiguration.php` are automatically migrated
through a silent upgrader when entering the Install Tool. If you modify
the settings in `AdditionalConfiguration.php` or rely on them inside an
extension you need to update those.


Affected Installations
======================

Installations which modify those settings directly or access them.


Migration
=========

The following table lists the changed configuration keys and the appropriate
values if these have changed.

============================   ===============================================
Old name                       New name
============================   ===============================================
im\_version\_5                 processor
                               The configuration value "im6" has been replaced
                               by "ImageMagick", "gm" by "GraphicsMagick"
im                             processor\_enabled
im\_v5effects                  processor\_effects
im\_noScaleUp                  processor\_allowUpscaling
im\_noFramePrepended           processor\_allowFrameSelection
im\_mask\_temp\_ext\_gif       processor\_allowTemporaryMasksAsPng
im\_path                       processor\_path
im\_path\_lzw                  processor\_path\_lzw
im\_stripProfileCommand        processor\_stripColorProfileCommand
im\_useStripProfileByDefault   processor\_stripColorProfileByDefault
colorspace                     processor\_colorspace
============================   ===============================================

.. index:: LocalConfiguration
