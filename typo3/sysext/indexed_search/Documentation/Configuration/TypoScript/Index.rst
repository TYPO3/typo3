.. include:: /Includes.rst.txt



.. _typoscript:

TypoScript
^^^^^^^^^^

[Still missing the major parts here. Just use the object browser for
now since that includes all options]

Following options live under :typoscript:`plugin.tx_indexedsearch.settings`.


.. _breadcrumbWrap:

breadcrumbWrap
""""""""""""""

.. container:: table-row

   Property
         breadcrumbWrap

   Data type
         :ref:`wrap <t3tsref:data-type-wrap>` + :ref:`optionSplit <t3tsref:objects-optionsplit>`

   Description
         This configuration is used to wrap a single page title in a search result item breadcrumb.

   Default
         / || /


.. _show-forbiddenrecords:

displayForbiddenRecords
"""""""""""""""""""""""

.. container:: table-row

   Property
         displayForbiddenRecords

   Data type
         boolean

   Description
         Explicitly display search hits although the visitor has no access to
         it.



.. _show-resultnumber:

displayResultNumber
"""""""""""""""""""

.. container:: table-row

   Property
         displayResultNumber

   Data type
         boolean

   Description
         Display the numbers of search results.

   Default
         0


.. _show-advancedsearchlink:

displayAdvancedSearchLink
"""""""""""""""""""""""""

.. container:: table-row

   Property
         displayAdvancedSearchLink

   Data type
         boolean

   Description
         Display the link to the advanced search page.

   Default
         1


.. _blind-numberOfResults:

blind.numberOfResults
"""""""""""""""""""""

.. container:: table-row

   Property
         blind.numberOfResults

   Data type
         string (list of integers, separated by comma)

   Description
         List of amount of results to be displayed per page.
         Sending a different amount via GET or POST will result in the default value
         being used to prevent DOS attacks.

   Default
         10,25,50,100



.. _search-rootpidlist:

rootPidList
"""""""""""

.. container:: table-row

   Property
         rootPidList

   Data type
         list of int

   Description
         A list of integer which should be root-pages to search from. Thus you
         can search multiple branches of the page tree by setting this property
         to a list of page id numbers.

         If this value is set to less than zero (eg. -1) searching will happen
         in ALL of the page tree with no regard to branches at all.

         Notice that by "root-page" we mean a website root defined by

         a TypoScript Template! If you just want to search in branches of your
         site, use the possibility of searching in levels.

   Default
         Empty, which fall backs to the current root-page id



.. _search-detect-sys-domain-records:

detectDomainRecords
"""""""""""""""""""

.. container:: table-row

   Property
         detectDomainRecords

   Data type
         boolean

   Description
         If set, then the search results are linked to the proper domains where
         they are found.



.. _search-detect-sys-domain-records-target:

detectDomainRecords.target
""""""""""""""""""""""""""

.. container:: table-row

   Property
         detectDomainRecords.target

   Data type
         string

   Description
         Target for external URLs.



.. _search-medialist:

mediaList
"""""""""

.. container:: table-row

   Property
         mediaList

   Data type
         string

   Description
         Restrict the file type list when searching for files.



.. _search-defaultfreeindexuidlist:

defaultFreeIndexUidList
"""""""""""""""""""""""

.. container:: table-row

   Property
         defaultFreeIndexUidList

   Data type
         string

   Description
         List of Indexing Configuration Uids to show as categories in search
         form. The order determines the order displayed in the search result.



.. _settings-exactcount:

exactCount
""""""""""

.. container:: table-row

   Property
         exactCount

   Data type
         boolean

   Description
         Force permission check for every record while displaying search
         results. Otherwise, records are only checked up to the current result
         page, and this might cause that the result counter does not print the
         exact number of search hits.

         By enabling this setting, the loop is not stopped, which causes an
         exact result count at the cost of an (obvious) slowdown caused by this
         overhead.

         See property "show.forbiddenRecords" for more information.



.. _search-skipextendtosubpageschecking:

searchSkipExtendToSubpagesChecking
""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         searchSkipExtendToSubpagesChecking

   Data type
         boolean

   Description
         If set to false (default), on each search the complete page tree will
         be transversed to check which pages are accessible, so that the
         extendToSubpages can be considered. This will work with a limited
         number of page-ids (which means most sites), but will result in slow
         performance on huge page trees.

         If set to true, then the final result rows are joined with the pages
         table to select pages that are currently accessible. This will speed
         up searching in very huge page trees, but on the other hand
         extendToSubpages will NOT be taken into account!

   Default
         0



.. _specialConfiguration-pid:

specialConfiguration.[pid]
""""""""""""""""""""""""""

.. container:: table-row

   Property
         specialConfiguration.[pid]

   Data type
         -

   Description
         "specialConfiguration" is an array of objects with properties that can customize
         certain behaviours of the display of a result row depending on it's
         position in the rootline. For instance you can define that all results
         which links to pages in a branch from page id 123 should have another
         page icon displayed. Of you can add a suffix to the class names so you
         can style that section differently.

         **Examples:**

         If a page "Contact" is found in a search for "address" and that
         "Contact" page is in the rootline "Frontpage [ID=23] > About us
         [ID=45] > Contact [ID=77]" then you should set the pid value to either
         "77" or "45". If "45" then all subpages including the "About us" page
         will have similar configuration.

         If the pid value is set to 0 (zero) it will apply to all pages.

         Please see the options below.



.. _specialConfiguration-pid-pageicon:

specialConfiguration.[pid].pageIcon
"""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         specialConfiguration.[pid].pageIcon

   Data type
         :ref:`IMAGE cObject <t3tsref:cobj-image>`

   Description
         Alternative page icon.



.. _specialConfiguration-pid-csssuffix:

specialConfiguration.[pid].CSSsuffix
""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         specialConfiguration.[pid].CSSsuffix

   Data type
         string

   Description
         A string that will be appended to the class-names of all the class-
         attributes used within the result row presentation. The prefix will be
         like this:

         **Example:**

         If "...CSSsuffix = doc" then eg. the class name "tx-indexedsearch-
         title" will be "tx-indexedsearch-title-doc"



.. _targetPid:

targetPid
"""""""""

.. container:: table-row

   Property
         targetPid

   Data type
         int

   Description
         Set the target page UID for the extbase variant of the plugin.



.. _results-titleCropAfter:

results.titleCropAfter
""""""""""""""""""""""

.. container:: table-row

   Property
         results.titleCropAfter

   Data type
         int

   Description
         Determines the length of the cropped title
         Defaults to 50


.. _results-titleCropSignifier:

results.titleCropSignifier
""""""""""""""""""""""""""

.. container:: table-row

   Property
         results.titleCropSignifier

   Data type
         string

   Description
         Determines the string being appended to a cropped title
         Defaults to "..."


.. _results-summaryCropAfter:

results.summaryCropAfter
""""""""""""""""""""""""

.. container:: table-row

   Property
         results.summaryCropAfter

   Data type
         int

   Description
         Determines the length of the cropped summary
         Defaults to 180


.. _results-summaryCropSignifier:

results.summaryCropSignifier
""""""""""""""""""""""""""""

.. container:: table-row

   Property
         results.summaryCropSignifier

   Data type
         string

   Description
         Determines the string being appended to a cropped summary
         Defaults to "..."


.. _results-hrefInSummaryCropAfter:

results.hrefInSummaryCropAfter
""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         results.hrefInSummaryCropAfter

   Data type
         int

   Description
         Determines the length of cropped links in the summary
         Defaults to 60


.. _results-hrefInSummaryCropSignifier:

results.hrefInSummaryCropSignifier
""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         results.hrefInSummaryCropSignifier

   Data type
         string

   Description
         Determines the string being appended to cropped links in the summary
         Defaults to "..."


.. _results-markupSW_summaryMax:

results.markupSW_summaryMax
"""""""""""""""""""""""""""

.. container:: table-row

   Property
         results.markupSW_summaryMax

   Data type
         int

   Description
         Maximum length of a summary to highlight searchwords in
         Defaults to 300


.. _results-markupSW_postPreLgd:

results.markupSW_postPreLgd
"""""""""""""""""""""""""""

.. container:: table-row

   Property
         results.markupSW_postPreLgd

   Data type
         int

   Description
         Determines the amount of characters to keep on both sides of the highlighted searchword
         Defaults to 60


.. _results-markupSW_postPreLgd_offset:

results.markupSW_postPreLgd_offset
""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         results.markupSW_postPreLgd_offset

   Data type
         int

   Description
         Determines the offset of characters from the right side of a highlighted searchword. Higher values will "move"
         the highlighted searchword further to the left.
         Defaults to 5


.. _results-markupSW_divider:

results.markupSW_divider
""""""""""""""""""""""""

.. container:: table-row

   Property
         results.markupSW_divider

   Data type
         string

   Description
         Divider for highlighted searchwords in the summary
         Defaults to "..."

.. _results-pathExcludeDoktypes:

results.pathExcludeDoktypes
"""""""""""""""""""""""""""

.. container:: table-row

   Property
         results.pathExcludeDoktypes

   Data type
         string

   Description
         Excludes doktypes in path.
         Defaults to ""

         **Example:**
         pathExcludeDoktypes = 254
         Exclude sys_folder (doktype: 254) in path for result.

         "/Footer(254)/Navi(254)/Imprint(1)" -> "/Imprint".

         pathExcludeDoktypes = 254,4
         Exclude sys_folder (doktype: 254) and shortcuts (doktype:4) in path for result.
         "/About-Us(254)/Company(4)/Germany(1)" -> "/Germany".



.. _forwardSearchWordsInResultLink:

forwardSearchWordsInResultLink.no_cache
"""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         forwardSearchWordsInResultLink.no_cache

   Data type
         boolean

   Description
         Toggles whether result links add the no_cache parameter.
         It is evaluated only if :typoscript:`forwardSearchWordsInResultLink = 1` is also set.


[tsref:plugin.tx\_indexedsearch]

