:navigation-title: Configuration

..  include:: /Includes.rst.txt
..  _configuration:

============================================
Configuration of the Linkvalidator extension
============================================

You can find the standard configuration in
:file:`EXT:linkvalidator/Configuration/page.tsconfig`.

This may serve as an example on how to configure the extension for
your needs.

..  note::

    When checking for broken links in the TYPO3 backend module or the
    corresponding Scheduler task, the page TSconfig of the selected start
    page is also applied to all subpages - when checking recursive.
    In case subpages should behave differently and therefore contain a
    different LinkHandler configuration, they must be checked individually.

..  contents:: Table of contents

..  _configuration-minimal:

Minimal configuration
=====================

It is recommended to at least fill out `httpAgentUrl` and `httpAgentEmail`.
The latter is only required if :php:`$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']`
is not set.

..  literalinclude:: _minimal.tsconfig
    :caption: config/sites/my-site/page.tsconfig

..  _reference:

TSconfig Reference
==================

You can set the following options in the TSconifg of a site, for example in
file :file:`config/sites/my-site/page.tsconfig` or in the global page TSconfig
file :file:`packages/my_sitepackage/Configuration/page.tsconfig` of your site
package.

You must prefix them with `mod.linkvalidator`, for example
:typoscript:`mod.linkvalidator.searchFields.pages = canonical_link`.

..  confval-menu::

..  _searchfields-key:

..  confval:: searchFields.[key]
    :name: tsconfig-searchfields-key
    :type: string
    :Path: mod.linkvalidator.linktypesConfig.searchFields.[key]

    Comma separated list of table fields in which to check for
    broken links. LinkValidator only checks fields that have
    been defined in :typoscript:`searchFields`.

    LinkValidator ships with sensible defaults that work well
    for the TYPO3 core, but additional third party extensions
    are not considered.

    ..  warning::

        Currently, LinkValidator will only detect links for fields if the
        TCA configuration meets one of these criteria:

        * at least one :ref:`softref <t3tca:tca_property_softref>`
        * type is set to :ref:`link <t3tca:columns-link>`
        * type is set to :ref:`email <t3tca:columns-email>`

        For this reason, it is currently not possible to check for
        `pages.media`. This will be fixed in the future.

        Examples for working fields:

        * `pages.canonical_link` (:php:`'type' => 'link'`)
        * `pages.url` (:php:`'softref' => 'url'`)
        * `sys_file_reference.link` (:php:`'type' => 'link'`)

        Example for not working fields:

        * `pages.media` (:php:`'type' => 'file'`)


    ..  code-block:: typoscript
        :caption: config/sites/my-site/page.tsconfig

        # Only check for "bodytext" in "tt_content":
        tt_content = bodytext


    ..  code-block:: typoscript
        :caption: Default values: EXT:linkvalidator/Configuration/page.tsconfig

        pages = media,url
        tt_content = bodytext,header_link,records

..  _linktypes:

..  confval:: linktypes
    :name: tsconfig-linktypes
    :type: string
    :Path: mod.linkvalidator.linktypesConfig.linktypes
    :Default: `db,file`

    Comma separated list of link types to check.

    **Possible values:**

    db
        Check links to database records.

    file
        Check links to files located in your local TYPO3 installation.

    external
        Check links to external URLs.

    This list may be extended by other extensions providing a
    :ref:`custom linktype implementation <linktype-implementation>`.

    ..  versionchanged:: 13.0
        The default was changed to exclude "external" link type.

    ..  warning::
        External links can lead to some :ref:`known issues <usagePitfallsExternalLinks>`.


..  _linktypes-config:
..  _linktypes-config-external:

..  confval:: linktypesConfig.external.httpAgentName
    :name: tsconfig-linktypesconfig-external-httpagentname
    :type: string
    :Path: mod.linkvalidator.linktypesConfig.linktypesConfig.external.httpAgentName
    :Default: `TYPO3 LinkValidator`

    Add descriptive name to be used as 'User-Agent' header when crawling
    external URLs.

..  confval:: linktypesConfig.external.httpAgentUrl
    :name: tsconfig-linktypesconfig-external-httpagenturl
    :type: string
    :Path: mod.linkvalidator.linktypesConfig.linktypesConfig.external.httpAgentUrl
    :Default: (empty string)

    Add descriptive name to be used as 'User-Agent' header when crawling
    external URLs.

    Add URL to be used in 'User-Agent' header when crawling
    external URLs.

..  confval:: linktypesConfig.external.httpAgentEmail
    :name: tsconfig-linktypesconfig-external-httpagentemail
    :type: string
    :Path: mod.linkvalidator.linktypesConfig.linktypesConfig.external.httpAgentEmail
    :Default: :php:` $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']`

    Add descriptive email used in 'User-Agent' header when crawling
    external URLs.

..  _checkhidden:

..  confval:: checkhidden
    :name: tsconfig-checkhidden
    :type: boolean
    :Path: mod.linkvalidator.checkhidden
    :Default: `0`

    If set, disabled pages and content elements are checked for broken
    links, too.

..  confval:: showCheckLinkTab
    :name: tsconfig-showchecklinktab
    :type: boolean
    :Path: mod.linkvalidator.showCheckLinkTab
    :Default: `1`

    If set, the backend module shows a "Check Links" tab, which you can
    use to perform the checks on demand.


    ..  figure:: /Images/CheckLinksTabVisible.png
        :alt: The Check links tab is visible

    The Check links tab is visible

    ..  note::

        Depending on the number of page levels to check and on the
        number of links in these pages, this check can take some time and need
        some resources. For large sites it might therefore be advisable to
        hide the tab.

    ..  note::

        LinkValidator uses a database table to store information
        about the broken links, which it found in your website. If
        showCheckLinkTab is set to 0, you must use the Scheduler task provided
        by LinkValidator to update this information.

..  _actionAfterEditRecord:

..  confval:: actionAfterEditRecord
    :name: tsconfig-actionaftereditrecord
    :type: string
    :Path: mod.linkvalidator.actionAfterEditRecord
    :Default: `recheck`
    :Possible values: `recheck` | `setNeedsRecheck`

    After a record is edited, the list of broken links may no longer be correct,
    because broken links were changed or removed or new broken links added. Due
    to this, the list of broken links should be updated.

    Possible values are:

    recheck
        The field is rechecked. (Warning: an RTE field may contain a number
        of links, rechecking may lead to delays.)
    setNeedsRecheck
        The entries in the list are marked as needing a recheck

..  _mail-fromname:

..  confval:: mail.fromname
    :name: tsconfig-mail-fromname
    :type: string
    :Path: mod.linkvalidator.mail.fromname
    :Default: :php:` $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']`

    Set the from name of the report mail sent by the cron script.


..  _mail-fromemail:

..  confval:: mail.fromemail
    :name: tsconfig-mail-fromemail
    :type: string
    :Path: mod.linkvalidator.mail.fromemail
    :Default: :php:` $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']`

    Set the from email of the report mail sent by the cron script.

..  _mail-replytoname:

..  confval:: mail.replytoname
    :name: tsconfig-mail-replytoname
    :type: string
    :Path: mod.linkvalidator.mail.replytoname

    Set the replyto name of the report mail sent by the cron script.

..  confval:: mail.replytoemail
    :name: tsconfig-mail-replytoemail
    :type: string
    :Path: mod.linkvalidator.mail.replytoemail

    Set the replyto email of the report mail sent by the cron script.


..  _mail-subject:

..  confval:: mail.subject
    :name: tsconfig-mail-subject
    :type: string
    :Path: mod.linkvalidator.mail.subject
    :Default: `TYPO3 LinkValidator report`

    Set the subject of the report mail sent by the cron script.

..  confval:: linktypesConfig
    :name: tsconfig-linktypesConfig
    :type: array
    :Path: mod.linkvalidator.linktypesConfig

    All settings within this key are advanced settings. In most cases, the defaults
    should be sufficient.

    ..  confval:: external.headers
        :name: tsconfig-linktypesConfig-external-headers
        :type: array
        :Path: mod.linkvalidator.linktypesConfig.external.headers
        :Default: (empty array)

        Additional set of HTTP headers to be passed when crawling URLs.

    ..  confval:: external.method
        :name: tsconfig-linktypesConfig-external-method
        :type: string
        :Path: mod.linkvalidator.linktypesConfig.external.method
        :Default: `HEAD`

        This specified which method is used for crawling URLs. By
        default, we use HEAD (which falls back to GET if HEAD fails).

        You can use GET as an alternative, but keep in mind that HEAD
        is a lightweight request and should be preferred while GET will
        fetch the remote web page (within the limits specified by range,
        see next option).

        "The HEAD method is identical to GET except that the server MUST
        NOT return a message-body in the response."
        (`w3 RFC2616 <https://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html>`__).

    ..  confval:: external.range
        :name: tsconfig-linktypesConfig-external-range
        :type: string
        :Path: mod.linkvalidator.linktypesConfig.external.range
        :Default: `0-4048`

        Additional HTTP request header 'Range' to be passed when crawling URLs.
        Use a string to specify the range (in bytes).

    ..  confval:: external.allowRedirects
        :name: tsconfig-linktypesConfig-external-allowredirects
        :type: boolean
        :Path: mod.linkvalidator.linktypesConfig.external.allowRedirects
        :Default: `0`

        ..  versionadded:: 14.0

        If enabled, HTTP redirects with external links are reported as problems.

    ..  confval:: external.timeout
        :name: tsconfig-linktypesConfig-external-timeout
        :type: integer
        :Path: mod.linkvalidator.linktypesConfig.external.timeout
        :Default: `20`

        HTTP request option. This is the total timeout of the request in
        seconds.

        If set, this overrides the timeout in
        :php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout']`
        which defaults to 0.

        ..  important::

            A value of 0 means no timeout, which may result in the request
            not terminating in some edge cases and can also result in Scheduler
            tasks to run indefinitely. There is an additional
            :php:`$GLOBALS['TYPO3_CONF_VARS']['HTTP']['connect_timeout']`
            which defaults to 10 seconds, but this may not be enough to lead to a request
            terminating in some edge cases.

..  _configuration-example:

Linkvalidator configuration example
===================================

..  literalinclude:: _example.tsconfig
    :caption: config/sites/my-site/page.tsconfig
