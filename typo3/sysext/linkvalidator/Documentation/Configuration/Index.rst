.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt



.. _configuration:

Configuration
-------------

You find the standard configuration in
:file:`EXT:linkvalidator/Configuration/TsConfig/Page/pagetsconfig.txt`.

This may serve you as an example on how to configure the extension for
your needs.


.. _reference:

Reference
^^^^^^^^^

You can set the following options in the TSconfig for a page (e.g. the
root page) and override them in user or groups TSconfig. You must
prefix them with mod.linkvalidator, e.g.
:code:`mod.linkvalidator.searchFields.pages = media`.



.. _searchfields-key:

searchFields.[key]
""""""""""""""""""

.. container:: table-row

   Property
         searchFields.[key]

   Data type
         string

   Description
         Comma separated list of table fields in which to check for broken
         links.

         **Example** :

         ::

            pages = media,url

   Default
         .. code-block:: typoscript

            pages = media,url
            tt_content = bodytext,header_link,records
            tt_news = bodytext,links



.. _linktypes:

linktypes
"""""""""

.. container:: table-row

   Property
         linktypes

   Data type
         string

   Description
         Comma separated list of hooks to load.

         **Possible values:**

         db: Check links to database records.

         file: Check links to files located in your local TYPO3 installation.

         external: Check links to external files.

         linkhandler: Check links provided by the extension "linkhandler".

         This list may be extended by other extensions providing a linktype
         checker, e.g. DAM.

   Default
         db,file,external



.. _checkhidden:

checkhidden
"""""""""""

.. container:: table-row

   Property
         checkhidden

   Data type
         boolean

   Description
         If set, disabled pages and content elements are checked for broken
         links, too.

   Default
         0



.. _showchecklinktab:

showCheckLinkTab
""""""""""""""""

.. container:: table-row

   Property
         showCheckLinkTab

   Data type
         boolean

   Description
         If set, the backend module shows a "Check Links" tab, which you can
         use to perform the checks on demand.


         .. figure:: ../Images/CheckLinksTabVisible.png
            :alt: The Check links tab is visible

            The Check links tab is visible

         .. note::

            Depending on the number of page levels to check and on the
            number of links in these pages, this check can take some time and need
            some resources. For large sites it might therefore be advisable to
            hide the tab.

         .. note::

            Linkvalidator uses a database table to store information
            about the broken links, which it found in your website. If
            showCheckLinkTab is set to 0, you must use the scheduler task provided
            by linkvalidator to update these information.

   Default
         1



.. _mail-fromname:

mail.fromname
"""""""""""""

.. container:: table-row

   Property
         mail.fromname

   Data type
         string

   Description
         Set the from name of the report mail sent by the cron script.

   Default
         Install Tool

         *defaultMailFromName*



.. _mail-fromemail:

mail.fromemail
""""""""""""""

.. container:: table-row

   Property
         mail.fromemail

   Data type
         string

   Description
         Set the from email of the report mail sent by the cron script.

   Default
         Install Tool

         *defaultMailFromAddress*



.. _mail-replytoname:

mail.replytoname
""""""""""""""""

.. container:: table-row

   Property
         mail.replytoname

   Data type
         string

   Description
         Set the replyto name of the report mail sent by the cron script.



.. _mail-replytoemail:

mail.replytoemail
"""""""""""""""""

.. container:: table-row

   Property
         mail.replytoemail

   Data type
         string

   Description
         Set the replyto email of the report mail sent by the cron script.



.. _mail-subject:

mail.subject
""""""""""""

.. container:: table-row

   Property
         mail.subject

   Data type
         string

   Description
         Set the subject of the report mail sent by the cron script.

   Default
         TYPO3 Linkvalidator report



[page:mod.linkvalidator; beuser:mod.linkvalidator]


.. _configuration-example:

Example
^^^^^^^

.. code-block:: typoscript

   mod.linkvalidator {
           searchFields {
                   pages = media,url
                   tt_content = bodytext,header_link,records
                   tt_news = bodytext,links
           }
           linktypes = db,file,external
           checkhidden = 0
           mail {
                   fromname = TYPO3 Linkvalidator
                   fromemail = no_reply@mydomain.com
                   replytoname =
                   replytoemail =
                   subject = TYPO3 Linkvalidator report
           }
   }


