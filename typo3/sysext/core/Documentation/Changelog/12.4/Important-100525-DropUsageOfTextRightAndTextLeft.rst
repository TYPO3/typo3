.. include:: /Includes.rst.txt

.. _important-100525-1681029540:

================================================================================
Important: #100525 - Dropped usage of .text(-*)-right and .text(-*)-left classes
================================================================================

See :issue:`100525`

Description
===========

The Core has dropped support for directional class names to
better support RTL languages. We are now preferring the logical
class names over the directional ones. This change also affects
the default RTE configuration.

In summary, that means we are dropping the classes :css:`.text-right`
and :css:`.text-left` and replacing them with their logical counterparts
:css:`.text-end` and :css:`.text-start`.

We are still shipping the :css:`.text-right` and :css:`.text-left` classes
with the default RTE content styling. Your content is
persisted as is and we have no intention of changing this.

You will see the following:

- Your content is still aligned as you set it once
- The alignment button will not be active anymore for :css:`.text-left`
  and :css:`.text-right`
- New alignments will now use :css:`.text-end` and :css:`.text-start`

While there is never a good time to introduce such a change,
we still think this will benefit us all over time.

If you want to follow us on that route, we suggest that you
add the following CSS to your frontend and or the custom
CSS for your RTE.

..  code-block:: css

    .text-end {
        text-align: end;
    }
    .text-start {
        text-align: start;
    }

See caniuse for compatibility, which is 96.23% at the time of writing.
For example: https://caniuse.com/?search=text-align%3A%20start

You need to adjust your RTE config, if you want to use
the old classes.

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/RTE/MyPreset.yaml

    editor:
      config:
        alignment:
          options:
            - { name: 'left', className: 'text-left' }
            - { name: 'center', className: 'text-center' }
            - { name: 'right', className: 'text-right' }
            - { name: 'justify', className: 'text-justify' }


.. index:: RTE, ext:rte_ckeditor
