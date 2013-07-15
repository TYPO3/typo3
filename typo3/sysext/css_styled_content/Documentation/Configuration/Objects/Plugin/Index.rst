.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt


.. _objects-plugin:

Insert Plugin (List)
""""""""""""""""""""

Plugins correspond to the list-type content element (the "list" meaning
the list of all plugins). The exact type of plugin used by a given
list-type content element is stored in the :code:`list_type` field of
the "tt_content" table.

Thus for rendering the list-type content element is one big
:ref:`t3tsref:cobj-case` object using the :code:`list_type` field as key.
When plugins are registered, the appopriate code is automatically added
to this main :ref:`t3tsref:cobj-case` object so that rendering requests
can be dispatched to the plugin's class.

Configuration of the plugin itself resides in :code:`plugin.[extension signature]`.
Some very old plugins however (like "tt\_board") are still registered
"manually" by including all their configuration in a top-level object.

