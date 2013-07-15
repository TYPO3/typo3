.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt


.. _objects-insert-records:

Insert Records (shortcut)
"""""""""""""""""""""""""

The insert records content element allows elements from any page to be
referenced in another page, so you can display the same element multiple times
without copying it.

Rendering is simply achieved by using a :ref:`t3tsref:cobj-records` object.
Before that a :ref:`t3tsref:cobj-case` object is used basing itself
on the :code:`layout` field of table "tt_content". This makes it possible
to vary the rendering depending on the chosen layout.
