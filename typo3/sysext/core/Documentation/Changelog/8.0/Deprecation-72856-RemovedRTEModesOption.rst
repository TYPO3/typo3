
.. include:: /Includes.rst.txt

================================================
Deprecation: #72856 - Removed RTE "modes" option
================================================

See :issue:`72856`

Description
===========

The RTE "modes" option that was added to a RTE enabled TCA field in the "defaultExtras"
section has been removed.

The RTE is now loaded via the configuration from TSconfig, usually set by "modes"
or "overruleMode" (used by default), and loaded even without the RTE mode set in
the TCA field defaultExtras section.


Impact
======

Extension authors do not need to set the defaultExtras "mode=ts_css" parameter explicitly.


Migration
=========

When configuring a RTE field in a TYPO3 extension the defaultExtras part should bet
set to `richtext:rte_transform` instead of  `richtext:rte_transform[mode=ts_css]`
in order to render the RTE.


Flexform
--------

Example for an RTE Field, used in a Flexform with CMS 8 after migration


.. code-block:: xml

   <text>
       <TCEforms>
           <label>LLL:EXT:extension_name/Resources/Private/Language/locallang_db.xlf:flexform.text.element.labelname</label>
           <config>
               <type>text</type>
               <size>10</size>
               <rows>5</rows>
               <enableRichtext>true</enableRichtext>
           </config>
           <defaultExtras>
               <richtext>rte_transform</richtext>
           </defaultExtras>
       </TCEforms>
   </text>


.. index:: TSConfig, Backend, RTE
