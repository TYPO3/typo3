.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _administration:

Administration
--------------

As this extensions needs to be implemented by other extensions, it
doesn't provide any additional information except the rendering of
tasks.


.. _hide-tasks:

Hide tasks
^^^^^^^^^^

It is possible to hide tasks from users by using the following
TSconfig (User TSconfig):

.. code-block:: typoscript

   taskcenter {
           <extension-key>.<task-class>= 0
   }

Be aware that the parts between the :code:`<` and :code:`>` need to be replace by the
actual extension key and class name.

