.. include:: /Includes.rst.txt

.. _important-100847-1686218342:

===================================================
Important: #100847 - Added font plugin to CKEditor5
===================================================

See :issue:`100847`

Description
===========

The font plugin has been added to the CKEditor5.
In order to use the font plugin, the RTE configuration needs to be adapted:

.. code-block:: yaml

    editor:
      config:
        toolbar:
          items:
            # add button to select font family
            - fontFamily
            # add button to select font size
            - fontSize
            # add button to select font color
            - fontColor
            # add button to select font background color
            - fontBackgroundColor

        fontColor:
          colors:
            - { label: 'Orange', color: '#ff8700' }
            - { label: 'Blue', color: '#0080c9' }
            - { label: 'Green', color: '#209d44' }

        fontBackgroundColor:
          colors:
            - { label: 'Stage orange light', color: '#fab85c' }

        fontFamily:
          options:
            - 'default'
            - 'Arial, sans-serif'

        fontSize:
          options:
            - 'default'
            - 18
            - 21

        importModules:
          - { 'module': '@ckeditor/ckeditor5-font', 'exports': ['Font'] }


More information can be found in the official documentation_.


.. _documentation: https://ckeditor.com/docs/ckeditor5/latest/features/font.html

.. index:: RTE, ext:rte_ckeditor
