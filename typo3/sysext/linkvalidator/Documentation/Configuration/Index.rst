.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt

.. highlight:: typoscript

.. _configuration:

Configuration
-------------

You can find the standard configuration in
:file:`EXT:linkvalidator/Configuration/TsConfig/Page/pagetsconfig.tsconfig`.

This may serve as an example on how to configure the extension for
your needs.


Minimal Configuration
^^^^^^^^^^^^^^^^^^^^^

It is recommended to at least fill out `httpAgentUrl` and `httpAgentEmail`.
The latter is only required if :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']`
is not set.

::

    mod.linkvalidator.linktypesConfig.external.httpAgentUrl =
    mod.linkvalidator.linktypesConfig.external.httpAgentEmail =


.. _reference:

Reference
^^^^^^^^^

You can set the following options in the TSconfig for a page (e.g. the
root page) and override them in user or groups TSconfig. You must
prefix them with mod.linkvalidator, e.g.
:ts:`mod.linkvalidator.searchFields.pages = canonical_link`.


.. _searchfields-key:

searchFields.[key]
""""""""""""""""""

.. container:: table-row

   Property
         searchFields.[key]

   Data type
         string

   Description
         Comma separated list of table fields in which to check for
         broken links. Linkvalidator only checks fields that have
         been defined in :ts:`searchFields`.

         Linkvalidator ships with sensible defaults that work well
         for the TYPO3 core, but additional third party extensions
         are not considered.

         .. warning::

            Currently, Linkvalidator can only detect links for fields having at
            least one :ref:`softref <columns-input-properties-softref>` set in their TCA configuration.

            For this reason, it is currently not possible to check for
            `pages.media`. This will be fixed in the future.

            Examples for working fields:

                * `pages.canonical_link`
                * `pages.url`

            Examples for not working fields:

            * `pages.media`


    Examples

          Only check for `bodytext` in `tt_content`:

          .. code-block:: typoscript

             tt_content = bodytext

   Default
         .. code-block:: typoscript

            pages = media,url
            tt_content = bodytext,header_link,records



.. _linktypes:

linktypes
"""""""""

.. container:: table-row

   Property
         linktypes

   Data type
         string

   Description
         Comma separated list of link types to check.

         **Possible values:**

         db: Check links to database records.

         file: Check links to files located in your local TYPO3 installation.

         external: Check links to external files.

         linkhandler: Check links provided by the extension "linkhandler".

         This list may be extended by other extensions providing a linktype
         checker.

   Default
         db,file,external



.. _linktypes-config-external:

linktypesConfig.external.httpAgentName
""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         linktypesConfig.external.httpAgentName

   Data type
         string

   Description
         Add descriptive name to be used as 'User-Agent' when crawling
         external URLs.

   Default
         TYPO3 Linkvalidator





linktypesConfig.external.httpAgentUrl
"""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         linktypesConfig.external.httpAgentUrl

   Data type
         string

   Description
         Add URL to be used in 'User-Agent' when crawling
         external URLs.

   Default



linktypesConfig.external.httpAgentEmail
"""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         linktypesConfig.external.httpAgentEmail

   Data type
         string

   Description
         Add descriptive email used in 'User-Agent' when crawling
         external URLs. If none is set here,
         :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']`
         is used.

   Default



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
            by linkvalidator to update this information.

   Default
         1



.. _actionAfterEditRecord:

actionAfterEditRecord
"""""""""""""""""""""

.. container:: table-row

   Property
         actionAfterEditRecord

   Data type
         string

   Default
         recheck

   Possible values
         recheck | setNeedsRecheck

   Description
         After a record is edited, the list of broken links may no longer be correct,
         because broken links were changed or removed or new broken links added. Due
         to this, the list of broken links should be updated.

         Possible values are:

         * **recheck**: The field is rechecked. (Warning: an RTE field may contain a number
           of links, rechecking may lead to delays.)
         * **setNeedsRecheck**: The entries in the list are marked as needing a recheck


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



.. hint::

    The following are advanced settings. In most cases, the defaults
    should be sufficient.



linktypesConfig.external.headers
""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         linktypesConfig.external.headers

   Data type
         array

   Description
         Additional set of HTTP headers to be passed when crawling URLs.

   Default


linktypesConfig.external.method
"""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         linktypesConfig.external.headers

   Data type
         array

   Description
         This specified which method is used for crawling URLs. By
         default, we use HEAD (which falls back to GET if HEAD fails).

         You can use GET as an alternative, but keep in mind that HEAD
         is a lightweight request and should be preferred while GET will
         fetch the remote web page (within the limits specified by range,
         see next option).

         "The HEAD method is identical to GET except that the server MUST
         NOT return a message-body in the response."
         (`w3 RFC2616 <https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html>`__).


   Default
         HEAD


linktypesConfig.external.range
""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         linktypesConfig.external.headers

   Data type
        string

   Description
        Additional HTTP request header 'Range' to be passed when crawling URLs.
        Use a string to specify the range (in bytes).

   Default
         0-4048


.. _configuration-example:

Example
^^^^^^^

.. code-block:: typoscript

   mod.linkvalidator {
           searchFields {
                   pages = url,canonical_link
                   tt_content = bodytext,header_link,records
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
           external {
                   httpAgentUrl = https://example.com/info.html
                   httpAgentEmail = info@example.com
           }
   }


