.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _typoscript:

TypoScript
^^^^^^^^^^

[Still missing the major parts here. Just use the object browser for
now since that includes all options]


.. _templatefile:

templateFile
""""""""""""

.. container:: table-row

   Property
         templateFile

   Data type
         resource

   Description
         The template file, see examples in typo3/sysext/indexed\_search/pi/.


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

show.forbiddenRecords
"""""""""""""""""""""

.. container:: table-row

   Property
         show.forbiddenRecords

   Data type
         boolean

   Description
         Explicitly display search hits although the visitor has no access to
         it.



.. _show-resultnumber:

show.resultNumber
"""""""""""""""""

.. container:: table-row

   Property
         show.resultNumber

   Data type
         boolean

   Description
         Display the numbers of search results.


.. _show-advancedsearchlink:

show.advancedSearchLink
"""""""""""""""""""""""

.. container:: table-row

   Property
         show.advancedSearchLink

   Data type
         boolean

   Description
         Display the link to the advanced search page.

   Default
         1



.. _search-rootpidlist:

search.rootPidList
""""""""""""""""""

.. container:: table-row

   Property
         search.rootPidList

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
         The current root-page id



.. _search-detect-sys-domain-records:

search.detect\_sys\_domain\_records
"""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         search.detect\_sys\_domain\_records

   Data type
         boolean

   Description
         If set, then the search results are linked to the proper domains where
         they are found.



.. _search-detect-sys-domain-records-target:

search.detect\_sys\_domain\_records.target
""""""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         search.detect\_sys\_domain\_records.target

   Data type
         string

   Description
         Target for external URLs.



.. _search-medialist:

search.mediaList
""""""""""""""""

.. container:: table-row

   Property
         search.mediaList

   Data type
         string

   Description
         Restrict the file type list when searching for files.



.. _search-defaultfreeindexuidlist:

search.defaultFreeIndexUidList
""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         search.defaultFreeIndexUidList

   Data type
         string

   Description
         List of Indexing Configuration Uids to show as categories in search
         form. The order determines the order displayed in the search result.



.. _search-exactcount:

search.exactCount
"""""""""""""""""

.. container:: table-row

   Property
         search.exactCount

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

search.skipExtendToSubpagesChecking
"""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         search.skipExtendToSubpagesChecking

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
         false



.. _specconfs-pid:

specConfs.[pid]
"""""""""""""""

.. container:: table-row

   Property
         specConfs.[pid]

   Data type
         -

   Description
         "specConfs" is an array of objects with properties that can customize
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



.. _specconfs-pid-pageicon:

specConfs.[pid].pageIcon
""""""""""""""""""""""""

.. container:: table-row

   Property
         specConfs.[pid].pageIcon

   Data type
         :ref:`IMAGE cObject <t3tsref:cobj-image>`

   Description
         Alternative page icon.



.. _specconfs-pid-csssuffix:

specConfs.[pid].CSSsuffix
"""""""""""""""""""""""""

.. container:: table-row

   Property
         specConfs.[pid].CSSsuffix

   Data type
         string

   Description
         A string that will be appended to the class-names of all the class-
         attributes used within the result row presentation. The prefix will be
         like this:

         **Example:**

         If "...CSSsuffix = doc" then eg. the class name "tx-indexedsearch-
         title" will be "tx-indexedsearch-title-doc"



.. _whatis-stdwrap:

whatis\_stdWrap
"""""""""""""""

.. container:: table-row

   Property
         whatis\_stdWrap

   Data type
         :ref:`stdWrap <t3tsref:stdwrap>`

   Description
         Parse input through the stdWrap function


.. _resultlist-stdWrap:

resultlist\_stdWrap
"""""""""""""""""""

.. container:: table-row

   Property
         resultlist\_stdWrap

   Data type
         :ref:`stdWrap <t3tsref:stdwrap>`

   Description
         Parse the result list through the stdWrap function


.. _results-titleCropAfter:

results\_titleCropAfter
"""""""""""""""""""""""

.. container:: table-row

   Property
         results\_titleCropAfter

   Data type
         int

   Description
         Determines the length of the cropped title
         Defaults to 50


.. _results-titleCropSignifier:

results\_titleCropSignifier
"""""""""""""""""""""""""""

.. container:: table-row

   Property
         results\_titleCropSignifier

   Data type
         string

   Description
         Determines the string being appended to a cropped title
         Defaults to "..."


.. _results-summaryCropAfter:

results\_summaryCropAfter
"""""""""""""""""""""""""

.. container:: table-row

   Property
         results\_summaryCropAfter

   Data type
         int

   Description
         Determines the length of the cropped summary
         Defaults to 180


.. _results-summaryCropSignifier:

results\_summaryCropSignifier
"""""""""""""""""""""""""""""

.. container:: table-row

   Property
         results\_summaryCropSignifier

   Data type
         string

   Description
         Determines the string being appended to a cropped summary
         Defaults to "..."


.. _results-hrefInSummaryCropAfter:

results\_hrefInSummaryCropAfter
"""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         results\_hrefInSummaryCropAfter

   Data type
         int

   Description
         Determines the length of cropped links in the summary
         Defaults to 60


.. _results-hrefInSummaryCropSignifier:

results\_hrefInSummaryCropSignifier
"""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         results\_hrefInSummaryCropSignifier

   Data type
         string

   Description
         Determines the string being appended to cropped links in the summary
         Defaults to "..."


.. _results-markupSW_summaryMax:

results\_markupSW_summaryMax
""""""""""""""""""""""""""""

.. container:: table-row

   Property
         results\_markupSW_summaryMax

   Data type
         int

   Description
         Maximum length of a summary to highlight searchwords in
         Defaults to 300


.. _results-markupSW_postPreLgd:

results\_markupSW_postPreLgd
""""""""""""""""""""""""""""

.. container:: table-row

   Property
         results\_markupSW_postPreLgd

   Data type
         int

   Description
         Determines the amount of characters to keep on both sides of the highlighted searchword
         Defaults to 60


.. _results-markupSW_postPreLgd_offset:

results\_markupSW_postPreLgd_offset
"""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         results\_markupSW_postPreLgd_offset

   Data type
         int

   Description
         Determines the offset of characters from the right side of a highlighted searchword. Higher values will "move"
         the highlighted searchword further to the left.
         Defaults to 5


.. _results-markupSW_divider:

results\_markupSW_divider
"""""""""""""""""""""""""

.. container:: table-row

   Property
         results\_markupSW_divider

   Data type
         string

   Description
         Divider for highlighted searchwords in the summary
         Defaults to "..."


.. _linkSectionTitles-stdWrap:

linkSectionTitles
"""""""""""""""""

.. container:: table-row

   Property
         linkSectionTitles

   Data type
         boolean

   Description
         Toggles whether section titles are linked or not


.. _forwardSearchWordsInResultLink:

forwardSearchWordsInResultLink.no_cache
"""""""""""""""""""""""""""""""""""""""

.. container:: table-row

   Property
         forwardSearchWordsInResultLink.no_cache

   Data type
         boolean

   Description
         Toggles whether result links add the no_cache parameter


[tsref:plugin.tx\_indexedsearch]

