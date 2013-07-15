.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _css-styling:

Out of the box CSS styling
--------------------------

This extension comes with a working CSS, which will be auto-generated
and added to the rendered pages. This auto-generated CSS will be
externalized with :code:`config.inlineStyle2TempFile = 1` which is set
by default (see :ref:`config reference in the TSRef <t3tsref:config>`).
This provides the most "out-of-the-box" experience, because you can now
influence the appearance through some settings in the CONSTANT EDITOR
(e.g. border, spacing, etc).

But you can also avoid this auto-generated CSS and choose to include
the CSS responsible for this plugin in your own .css files. To do so,
include this in your TypoScript Template:

::

   plugin.tx_cssstyledcontent._CSS_DEFAULT_STYLE >


or since TYPO3 CMS 4.6, you can set::

	config.removeDefaultCss = 1


which also affects plugins providing some default CSS.

Be aware that some settings in the external CSS
influence the rendering that needs to be done in the plugin. Thus some
settings that are done in CSS have to be specified in TypoScript too,
so that our plugin knows how to handle them. Basically those are
settings that influence spacing and borders and they can be set in the
CONSTANT EDITOR.

So you adapt your CSS to your wishes and then go to the CONSTANT
EDITOR and reflect these settings in these constants:

- **colSpace**: The space between columns of images (in pixels)

- **rowSpace**: The space after each row of images (in pixels)

- **textMargin**: The space from the imageblock to the text (in case of
  in-text rendering) (in pixels)

- **borderSpace**: The space that the borders around images take (in
  pixels)

- **borderThick**: The thickness of borders (in pixels)

