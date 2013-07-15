.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt


.. _objects-textpic:

Text & Image (textpic)
""""""""""""""""""""""

Text & Image-type content elements are rendered by combining
the rendering of the :ref:`Text-type <objects-text>` and
:ref:`Image-type <objects-image>` content elements, as can be
seen in this excerpt of the TypoScript setup::

	tt_content.textpic = COA
	tt_content.textpic {
		10 = COA
		...
		10.10 = < lib.stdheader

		20  = < tt_content.image.20
		20 {
			text.10 = COA
			text.10 {
				...
			}
			text.20 = < tt_content.text.20
			...
		}
	}


.. warning::

   For this particular element type, :ref:`lib.stdheader <setup-lib-stdheader>`
   is not found in the usual place (see code above).
