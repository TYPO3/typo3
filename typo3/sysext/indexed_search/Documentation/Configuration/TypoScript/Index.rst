..  include:: /Includes.rst.txt

..  _typoscript:

==========
TypoScript
==========

..  _typoscriptPlugin:

Plugin settings
===============

..  versionchanged:: 13.3
    It is recommended to change the settings via the :ref:`site-set` whenever
    possible.

Each of the following options is defined for the TypoScript setup path
:typoscript:`plugin.tx_indexedsearch.settings`.

..  contents:: Table of Contents
    :depth: 2
    :local:

..  _targetPid:

Target pid
----------

..  confval:: targetPid

    :Type: boolean
    :Default: empty
    :Path: plugin.tx_indexedsearch.settings

    Set the target page ID for the Extbase variant of the plugin. An empty
    value (default) falls back to the current page ID.

..  _show-advancedsearchlink:

Display advanced search link
----------------------------

..  confval:: displayAdvancedSearchLink

    :Type: boolean
    :Default: 1
    :Path: plugin.tx_indexedsearch.settings

    Display the link to the advanced search page.

..  _show-resultnumber:

Display result number
---------------------

..  confval:: displayResultNumber

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

    Display the numbers of search results.

..  _breadcrumbWrap:

Breadcrumb wrap
---------------

..  confval:: breadcrumbWrap

    :Type: :ref:`wrap <t3tsref:data-type-wrap>` + :ref:`optionSplit <t3tsref:optionsplit>`
    :Default: / || /
    :Path: plugin.tx_indexedsearch.settings

    This configuration is used to wrap a single page title in the breadcrumb of
    a search result item.

..  _show-level1sections:

Display level 1 sections
------------------------

..  confval:: displayLevel1Sections

    :Type: boolean
    :Default: 1
    :Path: plugin.tx_indexedsearch.settings

    This selects the first menu for the "sections" selector - so it can be
    searched in sections.

..  _show-level2sections:

Display level 2 sections
------------------------

..  confval:: displayLevel2Sections

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

    This selects the secondary menu for the "sections" selector - so it can
    be searched in sub sections. This setting only has an effect if
    :ref:`displayLevel1Sections <show-level1sections>` is true.

..  _show-Levelxalltypes:

Display level X all types
-------------------------

..  confval:: displayLevelxAllTypes

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

    Loaded are, by default:

    -   the subpages of the given page IDs of
        :ref:`rootPidList <search-rootpidlist>`, if
        :ref:`displayLevel1Sections <show-level1sections>` is true, and
    -   the subpages of the second level, if
        :ref:`displayLevel2Sections <show-level2sections>` is true.

    If :typoscript:`displayLevelxAllTypes` is set to true, then the page
    records for all evaluated IDs are loaded directly.

..  _show-forbiddenrecords:

Display forbidden records
-------------------------

..  confval:: displayForbiddenRecords

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

    Explicitly display search hits, although the visitor has no access to it.

..  _search-medialist:

Media list
----------

..  confval:: mediaList

    :Type: string
    :Default: empty
    :Path: plugin.tx_indexedsearch.settings

    Restrict the file type list when searching for files.

..  _search-rootpidlist:

Root pid list
-------------

..  confval:: rootPidList

    :Type: string (list of integers, separated by comma)
    :Default: empty
    :Path: plugin.tx_indexedsearch.settings

    A list of integers which should be root pages to search from. Thus you
    can search multiple branches of the page tree by setting this property
    to a list of page ID numbers.

    If this value is set to less than zero (eg. -1), the search will be
    performed in ALL parts of the page tree without regard to branches at all.
    An empty value (default) falls back to the current root page ID.

    ..  note::
        By "root page" we mean a website root defined by a TypoScript record!
        If you just want to search in branches of your site, use the possibility
        of searching in levels.

..  _search-page-links:

Page links
----------

..  confval:: page_links

    :Type: int
    :Default: 10
    :Path: plugin.tx_indexedsearch.settings

    The maximum number of result pages is defined here.

Default free index UID list
---------------------------

..  confval:: defaultFreeIndexUidList

    :Type: string (list of integers, separated by comma)
    :Default: empty
    :Path: plugin.tx_indexedsearch.settings

    List of Indexing Configuration UIDs to show as categories in the search
    form. The order determines the order displayed in the search result.

..  _settings-exactcount:

Exact count
-----------

..  confval:: exactCount

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

    Force permission check for every record while displaying search
    results. Otherwise, records are only checked up to the current result
    page, and this might cause that the result counter does not print the
    exact number of search hits.

    By enabling this setting, the loop is not stopped, which causes an
    exact result count at the cost of an (obvious) slowdown caused by this
    overhead.

    See property :ref:`show.forbiddenRecords <show-forbiddenrecords>` for more
    information.

..  _results:

Results
-------

..  confval:: results

    :Type: Array
    :Default: empty
    :Path: plugin.tx_indexedsearch.settings

    Various crop/offset settings for single result items.

..  _results-titleCropAfter:

Length of the cropped results title
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: results.titleCropAfter

    :Type: int
    :Default: 50
    :Path: plugin.tx_indexedsearch.settings

    Determines the length of the cropped title.

..  _results-titleCropSignifier:

Crop signifier for results title
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: results.titleCropSignifier

    :Type: string
    :Default: ...
    :Path: plugin.tx_indexedsearch.settings

    Determines the string being appended to a cropped title.

..  _results-summaryCropAfter:

Length of the cropped summary
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: results.summaryCropAfter

    :Type: int
    :Default: 180
    :Path: plugin.tx_indexedsearch.settings

    Determines the length of the cropped summary.

..  _results-summaryCropSignifier:

Crop signifier for the summary
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: results.summaryCropSignifier

    :Type: string
    :Default: ...
    :Path: plugin.tx_indexedsearch.settings

    Determines the string being appended to a cropped summary.

..  _results-hrefInSummaryCropAfter:

Length of cropped links in summary
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: results.hrefInSummaryCropAfter

    :Type: int
    :Default: 60
    :Path: plugin.tx_indexedsearch.settings

    Determines the length of cropped links in the summary.

..  _results-hrefInSummaryCropSignifier:

Crop signifier for links in summary
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: results.hrefInSummaryCropSignifier

    :Type: string
    :Default: ...
    :Path: plugin.tx_indexedsearch.settings

    Determines the string being appended to cropped links in the summary.

..  _results-markupSW_summaryMax:

Length of a summary to highlight search words
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: results.markupSW_summaryMax

    :Type: int
    :Default: 300
    :Path: plugin.tx_indexedsearch.settings

    Maximum length of a summary to highlight search words in.

..  _results-markupSW_postPreLgd:

Character count next to highlighted search word
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: results.markupSW_postPreLgd

    :Type: int
    :Default: 60
    :Path: plugin.tx_indexedsearch.settings

    Determines the amount of characters to keep on both sides of the
    highlighted search word.

..  _results-markupSW_postPreLgd_offset:

Characters offset from the right side of a highlighted search word
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: results.markupSW_postPreLgd_offset

    :Type: int
    :Default: 5
    :Path: plugin.tx_indexedsearch.settings

    Determines the offset of characters from the right side of a
    highlighted search word. Higher values will "move" the highlighted
    search word further to the left.

..  _results-markupSW_divider:

Divider for highlighted search words
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: results.markupSW_divider

    :Type: string
    :Default: ...
    :Path: plugin.tx_indexedsearch.settings

    Divider for highlighted search words in the summary.

..  _results-pathExcludeDoktypes:

Excludes doktypes in path
~~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: results.pathExcludeDoktypes

    :Type: string
    :Default: empty
    :Path: plugin.tx_indexedsearch.settings

    Excludes doktypes in rootline.

    **Example:**

    ..  code-block:: typoscript

        plugin.tx_indexedsearch.settings {
            results {
                pathExcludeDoktypes = 254
            }
        }

    Exclude folder (doktype: 254) in path for the result.

    `/Footer(254)/Navi(254)/Imprint(1)` -> `/Imprint`.

    ..  code-block:: typoscript

        plugin.tx_indexedsearch.settings {
            results {
                pathExcludeDoktypes = 254,4
            }
        }

    Exclude folder (doktype: 254) and shortcuts (doktype: 4) in path
    for result.

    `/About-Us(254)/Company(4)/Germany(1)` -> `/Germany`.

..  _defaultOptions:

Default options
---------------

..  confval:: defaultOptions

    :Type: Array
    :Default: empty
    :Path: plugin.tx_indexedsearch.settings

    Setting of default values.

    Please see the options below.

..  _defaultOptions-defaultOperand:

Default: Operand
~~~~~~~~~~~~~~~~

..   confval:: defaultOptions.defaultOperand

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

    0
        All words (AND)
    1
        Any words (OR)

..  _defaultOptions-sections:

Default: Sections
~~~~~~~~~~~~~~~~~

..  confval:: defaultOptions.sections

    :Type: string (list of integers, separated by comma)
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

..  _defaultOptions-freeIndexUid:

Default: Free index UID
~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: defaultOptions.freeIndexUid

    :Type: int
    :Default: -1
    :Path: plugin.tx_indexedsearch.settings

..  _defaultOptions-mediaType:

Default: Media type
~~~~~~~~~~~~~~~~~~~

..  confval:: defaultOptions.mediaType

    :Type: int
    :Default: -1
    :Path: plugin.tx_indexedsearch.settings

..  _defaultOptions-sortOrder:

Default: Sort order
~~~~~~~~~~~~~~~~~~~

..  confval:: defaultOptions.sortOrder

    :Type: string
    :Default: rank_flag
    :Path: plugin.tx_indexedsearch.settings

..  _defaultOptions-languageUid:

Default: Language UID
~~~~~~~~~~~~~~~~~~~~~

..  confval:: defaultOptions.languageUid

    :Type: string
    :Default: current
    :Path: plugin.tx_indexedsearch.settings

..  _defaultOptions-sortDesc:

Default: Sort desc
~~~~~~~~~~~~~~~~~~

..  confval:: defaultOptions.sortDesc

    :Type: boolean
    :Default: 1
    :Path: plugin.tx_indexedsearch.settings

..  _defaultOptions-searchType:

Default: Search type
~~~~~~~~~~~~~~~~~~~~

..  confval:: defaultOptions.searchType

    :Type: int
    :Default: 1
    :Path: plugin.tx_indexedsearch.settings

    Possible values are 0, 1 (any part of the word), 2, 3, 10 and 20
    (sentence).

..  _defaultOptions-extResume:

Default: Extended resume
~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: defaultOptions.extResume

    :Type: boolean
    :Default: 1
    :Path: plugin.tx_indexedsearch.settings

..  _blind:

Blind
-----

..  confval:: blind

    :Type: Array
    :Default: empty
    :Path: plugin.tx_indexedsearch.settings

    Blinding of option selectors / values in these (advanced search).

    Please see the options below.

..  _blind-searchType:

Blind: Search type
~~~~~~~~~~~~~~~~~~

..  confval:: blind.searchType

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

..  _blind-defaultOperand:

Blind: Default operand
~~~~~~~~~~~~~~~~~~~~~~

..  confval:: blind.defaultOperand

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

..  _blind-sections:

Blind: Sections
~~~~~~~~~~~~~~~

..  confval:: blind.sections

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

..  _blind-freeIndexUid:

Blind: Free index UID
~~~~~~~~~~~~~~~~~~~~~

..  confval:: blind.freeIndexUid

    :Type: boolean
    :Default: 1
    :Path: plugin.tx_indexedsearch.settings

..  _blind-mediaType:

Blind: Media type
~~~~~~~~~~~~~~~~~

..  confval:: blind.mediaType

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

..  _blind-sortOrder:

Blind: Sort order
~~~~~~~~~~~~~~~~~

..  confval:: blind.sortOrder

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

..  _blind-group:

Blind: Group
~~~~~~~~~~~~

..  confval:: blind.group

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

..  _blind-languageUid:

Blind: Language UID
~~~~~~~~~~~~~~~~~~~

..  confval:: blind.languageUid

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

..  _blind-desc:

Blind: Desc
~~~~~~~~~~~

..  confval:: blind.desc

    :Type: boolean
    :Default: 0
    :Path: plugin.tx_indexedsearch.settings

..  _blind-numberOfResults:

Blind: Number of results
~~~~~~~~~~~~~~~~~~~~~~~~

..  confval:: blind.numberOfResults

    :Type: string (list of integers, separated by comma)
    :Default: 10,25,50,100
    :Path: plugin.tx_indexedsearch.settings

    List of amount of results to be displayed per page.
    Sending a different amount via GET or POST will result in the default value
    being used to prevent DoS attacks.

..  _blind-extResume:

Blind: Extended resume
~~~~~~~~~~~~~~~~~~~~~~

..  confval:: blind.extResume

    :Type: boolean
    :Default: 1
    :Path: plugin.tx_indexedsearch.settings


[tsref:plugin.tx\_indexedsearch]
