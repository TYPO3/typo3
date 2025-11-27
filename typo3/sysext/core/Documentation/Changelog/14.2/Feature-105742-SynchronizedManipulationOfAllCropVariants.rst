..  include:: /Includes.rst.txt

..  _feature-105742-1755084132:

=================================================================
Feature: #105742 - Synchronized manipulation of all crop variants
=================================================================

See :issue:`105742`

Description
===========

The image manipulation wizard allows images to be cropped using multiple crop variants.
When there were many variants, each one had to be edited individually, and all
changes had to be made manually multiple times.
This became particularly tedious when the editor wanted to apply precisely the
same crop values across all variants.

This feature introduces a new checkbox that allows the editor to crop all variants simultaneously.
The prerequisite for enabling this checkbox is that all crop variants must be
defined with identical aspect ratios and other identical configuration
(apart from its title).

A new sub-option of the `cropVariants` TCA/TCEFORM option array called
`excludeFromSync` allows developers to exclude specific crop variants from being
affected by synchronized cropping.

This is useful, for example, when a special crop variant for a list view is
added to the standard set of crop variants, and its differing configuration
should still allow the other crop variants to be synchronizable.

Example
=======

The following code is the definition of the standard crop variants for a
bootstrap-based template.

All crop variants are defined identically (except the title) to enable the
synchronized cropping feature.

But for `tx_news`, a new crop variant for the listview is added, and
`excludeFromSync = 1` is used to specifically allow one exemption of the
synchronize-feature.

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
    TCEFORM.tx_news_domain_model_news.fal_media.config.overrideChildTca.columns.crop.config.cropVariants {
        listview {
            title = List view
            selectedRatio = default
            excludeFromSync = 1
            allowedAspectRatios {
                # [...] array of a custom aspect ratio definition
                # (or identical aspect ratios, but not taken into account
                # for synchronized cropping)
            }
        }
    }

Impact
======

The editor is now able to apply changes to the image aspect ratio and the image
cutting to several matching crop variants at once inside the image manipulation
cropping GUI.
Exemptions for specific `cropVariants` can be set.

..  index:: Backend, ext:core
