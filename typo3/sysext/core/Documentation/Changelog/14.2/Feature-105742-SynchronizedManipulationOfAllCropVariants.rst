..  include:: /Includes.rst.txt

..  _feature-105742-1755084132:

=================================================================
Feature: #105742 - Synchronized manipulation of all crop variants
=================================================================

See :issue:`105742`

Description
===========

The image manipulation wizard allows images to be cropped to multiple
crop variants. When many variants were present, each had to be edited
individually, requiring the same changes to be applied multiple times.
This was particularly tedious when an editor needed identical crop values
across all image variants.

This feature introduces a checkbox that allows editors to crop all image variants
simultaneously. This checkbox is available if all crop variants share
identical aspect ratios and configuration (except for the title).

`excludeFromSync` is a new sub-option of the `cropVariants` TCA/TCEFORM
configuration array which allows developers to exclude specific crop variants
from synchronized cropping.

This is useful, for example, when adding a special crop variant for a list
view that has a different configuration, while still allowing other crop
variants to be synchronized.

Example
=======

The following example defines standard crop variants for a Bootstrap-based
template.

All crop variants are configured identically (except for the title), which
enables synchronized cropping.

An additional crop variant for the `tx_news` list view is defined.
The option `excludeFromSync = 1` ensures that this variant is excluded
from synchronization.

..  code-block:: typoscript

    TCEFORM.sys_file_reference.crop.config.cropVariants {
        xxl {
            title = Very Large Desktop
            selectedRatio = NaN
            allowedAspectRatios {
                # [...] array of defined aspect ratios (identical!)
            }
        }

        xl {
            title = Large Desktop
            selectedRatio = NaN
            allowedAspectRatios {
                # [...] array of defined aspect ratios (identical!)
            }
        }

        # [...]
    }

    # Override for news extension
    TCEFORM.tx_news_domain_model_news.fal_media.config {
        overrideChildTca.columns.crop.config.cropVariants {
            listview {
                title = List view
                selectedRatio = default
                excludeFromSync = 1
                allowedAspectRatios {
                    # [...] array of custom aspect ratio definitions
                    # (or identical aspect ratios, but not considered for
                    # synchronized cropping)
                }
            }
        }
    }

Impact
======

Editors can now apply changes to image aspect ratios and cropping to
multiple matching crop variants in the image manipulation wizard.

Specific `cropVariants` can be excluded from synchronization.

..  index:: Backend, ext:core
