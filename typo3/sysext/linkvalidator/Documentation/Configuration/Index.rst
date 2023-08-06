.. include:: /Includes.rst.txt

.. highlight:: typoscript

.. _configuration:

Configuration
-------------

You can find the standard configuration in
:file:`EXT:linkvalidator/Configuration/page.tsconfig`.

This may serve as an example on how to configure the extension for
your needs.

.. note::

   When checking for broken links in the TYPO3 backend module or the
   corresponding Scheduler task, the page TSconfig of the selected start
   page is also applied to all subpages - when checking recursive.
   In case subpages should behave differently and therefore contain a
   different LinkHandler configuration, they must be checked individually.

Minimal configuration
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

You can set the following options in the TSconfig for a page (for example the
root page) and override them in user or groups TSconfig. You must
prefix them with mod.linkvalidator, for example
:typoscript:`mod.linkvalidator.searchFields.pages = canonical_link`.


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
         broken links. LinkValidator only checks fields that have
         been defined in :typoscript:`searchFields`.

         LinkValidator ships with sensible defaults that work well
         for the TYPO3 core, but additional third party extensions
         are not considered.

         .. warning::

            Currently, LinkValidator can only detect links for fields having at
            least one :ref:`softref <t3tca:columns-input-properties-softref>` set in their TCA configuration.

            For this reason, it is currently not possible to check for
            `pages.media`. This will be fixed in the future.

            Examples for working fields:

            * `pages.canonical_link`
            * `pages.url`

            Example for not working fields:

            * `pages.media`

   Example
         ::

            # Only check for "bodytext" in "tt_content":
            tt_content = bodytext

   Default
         ::

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

         external: Check links to external URLs.

         This list may be extended by other extensions providing a
         :ref:`custom linktype implementation <linktype-implementation>`.

         ..  warning::
             External links can lead to some :ref:`known issues<usagePitfallsExternalLinks>`.

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
         Add descriptive name to be used as 'User-Agent' header when crawling
         external URLs.

   Default
         TYPO3 LinkValidator





linktypesConfig.external.httpAgentUrl
"""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         linktypesConfig.external.httpAgentUrl

   Data type
         string

   Description
         Add URL to be used in 'User-Agent' header when crawling
         external URLs.

   Default
         (empty string)


linktypesConfig.external.httpAgentEmail
"""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         linktypesConfig.external.httpAgentEmail

   Data type
         string

   Description
         Add descriptive email used in 'User-Agent' header when crawling
         external URLs.

   Default
         $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']



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

            LinkValidator uses a database table to store information
            about the broken links, which it found in your website. If
            showCheckLinkTab is set to 0, you must use the Scheduler task provided
            by LinkValidator to update this information.

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
         TYPO3 LinkValidator report



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
         (empty array)


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

linktypesConfig.external.timeout
""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
        linktypesConfig.external.timeout

   Data type
        integer

   Description
        HTTP request option. This is the total timeout of the request in
        seconds.

        If set, this overrides the timeout in
        :php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout']`
        which defaults to 0.

        .. important::

           A value of 0 means no timeout, which may result in the request
           not terminating in some edge cases and can also result in Scheduler
           tasks to run indefinitely. There is an additional
           :php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['connect_timeout']`
           which defaults to 10 seconds, but this may not be enough to lead to a request
           terminating in some edge cases.

   Default
         20



.. _configuration-example:

Example
^^^^^^^

::

   mod.linkvalidator {
           searchFields {
                   pages = url,canonical_link
                   tt_content = bodytext,header_link,records
           }
           linktypes = db,file,external
           checkhidden = 0
           mail {
                   fromname = TYPO3 LinkValidator
                   fromemail = no_reply@mydomain.com
                   replytoname =
                   replytoemail =
                   subject = TYPO3 LinkValidator report
           }
           external {
                   httpAgentUrl = https://example.com/info.html
                   httpAgentEmail = info@example.com
           }
   }


