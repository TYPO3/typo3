.. include:: /Includes.rst.txt

======================================================================
Feature: #79812 - Allow overriding cropVariants for Image Manipulation
======================================================================

See :issue:`79812`

Description
===========

With the introduction of :issue:`75880` you now can define multiple cropVariants in TCA.
With this feature it is now possible to change or override these cropVariants via TSconfig.

Setting a FormEngine option through :typoscript:`TCEFORM.sys_file_reference.crop.config.cropVariants.*` does now work.


.. code-block:: typoscript

    TCEFORM.sys_file_reference.crop.config.cropVariants {
        default {
            title = Default desktop
            selectedRatio = NaN
            allowedAspectRatios {
                NaN {
                    title = free
                    value = 0.0
                }
            }
        }
        specialMobile {
            title = Our special mobile variant
            selectedRatio = NaN
            allowedAspectRatios {
                4:3 {
                    title = ratio 4/3
                    value = 1.3333333
                }
            }
        }
    }


Impact
======

It is not possible to change or override cropVariants via Page and User TSconfig.

.. index:: Backend, FAL, TSConfig
