.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _configuration:

Configuration
-------------

This Extension is configured by global :ref:`TypoScript config <configuration-global>`,
:ref:`typolink options <configuration-global-jumpurl-enable>` and
:ref:`configuration-typo3-conf-vars`.


.. _configuration-global:

Global TypoScript configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

JumpURL can be configured globally in the ``config`` TypoScript namespace.


.. ### BEGIN~OF~TABLE ###


.. _configuration-global-jumpurl-enable:

jumpurl\_enable
"""""""""""""""

.. container:: table-row

   Property
         jumpurl\_enable

   Data type
         boolean

   Description
         Enables JumpURL for all supported contexts for all generated links.


.. _configuration-global-jumpurl-mailto-disable:

jumpurl\_mailto\_disable
""""""""""""""""""""""""

.. container:: table-row

   Property
         jumpurl\_mailto\_disable

   Data type
         boolean

   Description
         Disables the use of JumpURL when linking to email-adresses.


.. ###### END~OF~TABLE ######


.. _configuration-typolink:

typolink settings
^^^^^^^^^^^^^^^^^

JumpURL options can be provided in the :ref:`typolink <t3tsref:typolink>` and the :ref:`filelink <t3tsref:filelink>`
configuration.

The ``typolink`` options can be provided directly in the ``typolink`` namespace, e.g.:

.. code-block:: typoscript

   mylink = TEXT
   mylink.value = typo3.org
   mylink.typolink.parameter = http://www.typo3.org
   mylink.typolink.jumpurl = 1


The :code:`filelink` options can be provided in the :code:`typolinkConfiguration` property:

.. code-block:: typoscript

   mylink = TEXT
   mylink.value = text.txt
   mylink.filelink.path = fileadmin/
   mylink.filelink.typolinkConfiguration.jumpurl = 1


The following options are available for JumpURLs.


.. ### BEGIN~OF~TABLE ###


.. _configuration-typolink-jumpurl:

jumpurl
"""""""

.. container:: table-row

   Property
         jumpurl

   Data type
         boolean

   Description
         Enables JumpURL for the current link if it points to an external URL or a file.

         Please note that this does not work for internal links or for email links.

         To enable JumpURL for email links the global setting needs to be used.


.. _configuration-typolink-jumpurl-force-disable:

jumpurl.forceDisable
""""""""""""""""""""

.. container:: table-row

   Property
         jumpurl.forceDisable

   Data type
         boolean

   Description
         Disables JumpURL.

         This will override the global setting config.jumpurl_enable for the current link.


.. _configuration-typolink-jumpurl-secure:

jumpurl.secure
""""""""""""""

.. container:: table-row

   Property
         jumpurl.secure

   Data type
         boolean

   Description
         Enables JumpURL secure. This option is only available for file links.

         If set, then the file pointed to by jumpurl is **not** redirected to, but rather it's read
         from the file and returned with a correct header.

         This option adds a hash and locationData to the URL and there MUST be access to the record
         in order to download the file.

         If the file  position on the server is furthermore secured by a .htaccess file preventing ANY
         access, you've got secure download here!


.. _configuration-typolink-jumpurl-secure-mime-types:

jumpurl.secure.mimeTypes
""""""""""""""""""""""""

.. container:: table-row

   Property
         jumpurl.secure.mimeTypes

   Data type
         string

   Description
         With this option you can specify an alternative mime type that is sent in the HTTP Content-Type
         header when the file is delivered to the user. By default the automatically detected mime type
         will be used.

         Syntax: [ext] = [MIME type1], [ext2] = [MIME type2], ...

         **Example:**

         .. code-block:: typoscript

            jumpurl.secure = 1
            jumpurl.secure.mimeTypes = pdf=application/pdf, doc=application/msword


.. ###### END~OF~TABLE ######


.. _configuration-typo3-conf-vars:

TYPO3_CONF_VARS
^^^^^^^^^^^^^^^

The setting :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']` can be used to disable
the referer check during jumpurl handling. By default the referring host must match the current
host, otherwise processing is stopped.

The setting :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']` is used for generating
the hashes submitted in the URLs.