.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _configuration:

Configuration Options
^^^^^^^^^^^^^^^^^^^^^

You can use Page TS Config to configure the look and text of the stage
notification emails


.. _stagenotificationemail-subject:

stageNotificationEmail.subject
""""""""""""""""""""""""""""""

(:code:`tx_version.workspaces.stageNotificationEmail.subject`)

.. container:: table-row

   Property
         stageNotificationEmail.subject

   Description
         The default subject for the stage notification email.

         Note that there are various markers you can use in the subject or
         message:

         ###SITE\_NAME###

         ###SITE\_URL###

         ###WORKSPACE\_TITLE###

         ###WORKSPACE\_UID###

         ###ELEMENT\_NAME###

         ###NEXT\_STAGE###

         ###COMMENT###

         ###USER\_REALNAME###

         ###USER\_USERNAME###

         ###RECORD\_PATH###

         ###RECORD\_TITLE###

   Default
         LLL:EXT:version/Resources/Private/Language/emails.xml:subject



.. _stagenotificationemail-message:

stageNotificationEmail.message
""""""""""""""""""""""""""""""

(:code:`tx_version.workspaces.stageNotificationEmail.message`)

.. container:: table-row

   Property
         stageNotificationEmail.message

   Description
         The default message for the stage notification email. Look at the
         Description of the subject for markers you can use

   Default
         LLL:EXT:version/Resources/Private/Language/emails.xml:message



.. _stagenotificationemail-additionalheaders:

stageNotificationEmail.additionalHeaders
""""""""""""""""""""""""""""""""""""""""

(:code:`tx_version.workspaces.stageNotificationEmail.additionalHeaders`)

.. container:: table-row

   Property
         stageNotificationEmail.additionalHeaders

   Description
         Additional Headers to be sent with the notification email.


Example
"""""""

.. code-block:: typoscript

   tx_version.workspaces.stageNotificationEmail.subject = Stage changed for ###ELEMENT_NAME###

