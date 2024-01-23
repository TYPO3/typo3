.. include:: /Includes.rst.txt

.. _feature-94501-1669163526:

===================================================
Feature: #94501 - FAL support for FlexFormProcessor
===================================================

See :issue:`94501`

Description
===========

The :php:`FlexFormProcessor` is now able to resolve :abbr:`FAL (File Abstraction Layer)`
references by its own.

Each FlexForm field, which should be resolved, needs a reference definition
to the :php:`foreign_match_fields`. This reference is later used in the
:php:`FilesProcessor` to resolve the correct FAL resource.

Example of an advanced TypoScript configuration, which processes the field
:typoscript:`my_flexform_field`, resolves its FAL references and assigns the
array to the :typoscript:`myOutputVariable` variable:

.. code-block:: typoscript

   10 = TYPO3\CMS\Frontend\DataProcessing\FlexFormProcessor
   10 {
     fieldName = my_flexform_field
     references {
       my_flex_form_group.my_flex_form_field = my_field_reference
     }
     as = myOutputVariable
   }

.. code-block:: xml

   <my_flex_form_group.my_flex_form_field>
      <label>LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:my_flex_form_field</label>
      <config>
         <type>file</type>
         <maxitems>9</maxitems>
         <foreign_selector_fieldTcaOverride>
            <config>
               <appearance>
                  <elementBrowserType>file</elementBrowserType>
                  <elementBrowserAllowed>gif,jpg,jpeg,png,svg</elementBrowserAllowed>
               </appearance>
            </config>
         </foreign_selector_fieldTcaOverride>
         <foreign_types type="array">
            <numIndex index="0">
               <showitem>--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette</showitem>
            </numIndex>
            <numIndex index="2">
               <showitem>--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette</showitem>
            </numIndex>
         </foreign_types>
         <appearance type="array">
            <headerThumbnail>
               <height>64</height>
               <width>64</width>
            </headerThumbnail>
            <enabledControls>
               <info>1</info>
               <dragdrop>0</dragdrop>
               <sort>1</sort>
               <hide>0</hide>
               <delete>1</delete>
               <localize>1</localize>
            </enabledControls>
            <createNewRelationLinkTitle>LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference</createNewRelationLinkTitle>
         </appearance>
         <behaviour>
            <localizationMode>select</localizationMode>
            <localizeChildrenAtParentLocalization>1</localizeChildrenAtParentLocalization>
         </behaviour>
         <overrideChildTca>
            <types type="array">
               <numIndex index="2">
                  <showitem>--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,--palette--;;filePalette</showitem>
               </numIndex>
            </types>
         </overrideChildTca>
         <allowed>jpg,png,svg,jpeg,gif</allowed>
      </config>
   </my_flex_form_group.my_flex_form_field>


Impact
======

FAL references within a FlexForm can now be resolved for the direct usage
in Fluid templates. This makes resolving the file references by using additional
data processors obsolete.

.. index:: Frontend, FlexForm, TypoScript, ext:frontend
