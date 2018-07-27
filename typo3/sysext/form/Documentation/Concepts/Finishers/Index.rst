.. include:: ../../Includes.txt


.. _concepts-finishers:

Finishers
=========

The form framework ships a bunch of finishers, which will be briefly
described here. For more details, please head to the API reference and check
out the section regarding :ref:`Finisher Options<apireference-finisheroptions>`.


.. _concepts-finishers-closurefinisher:

Closure finisher
----------------

The 'Closure finisher' can only be used within forms that are created
programmatically. It allows you to execute your own finisher code without
implementing/ declaring a finisher.


.. _concepts-finishers-confirmationfinisher:

Confirmation finisher
---------------------

The 'Confirmation finisher' is a simple finisher that outputs a given
text after the form has been submitted.


.. _concepts-finishers-deleteuploadsfinisher:

DeleteUploads finisher
----------------------

The 'DeleteUploads finisher' removes submitted files. Use this finisher,
for example, after the email finisher if you do not want to keep the files
within your TYPO3 installation.


.. _concepts-finishers-emailfinisher:

Email finisher
--------------

The 'Email finisher' sends an email to one recipient. EXT:form uses two
``EmailFinisher`` declarations with the identifiers ``EmailToReceiver`` and
``EmailToSender``.


.. _concepts-finishers-flashmessagefinisher:

FlashMessage finisher
---------------------

The 'FlashMessage finisher' is a simple finisher that adds a message to the
FlashMessageContainer.


.. _concepts-finishers-redirectfinisher:

Redirect finisher
-----------------

The 'Redirect finisher' is a simple finisher that redirects to another page.
Additional link parameters can be added to the URL.

.. note::

   This finisher stops the execution of all subsequent finishers in order to perform a redirect.
   Therefore, this finisher should always be the last finisher to be executed.

.. _concepts-finishers-savetodatabasefinisher:

SaveToDatabase finisher
-----------------------

The 'SaveToDatabase finisher' saves the data of a submitted form into a
database table.

.. _concepts-finishers-writecustomfinisher:

Write a custom finisher
-----------------------

:ref:`Learn how to create a custom finisher here.<concepts-frontendrendering-codecomponents-customfinisherimplementations>`

If you want to make the finisher configurable in the backend UI read :ref:`here<concepts-formeditor-extending-custom-finisher>`.
