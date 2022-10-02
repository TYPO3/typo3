.. include:: /Includes.rst.txt

.. _deprecation-97126:

==================================================
Deprecation: #97126 - TCEforms removed in FlexForm
==================================================

See :issue:`97126`

Description
===========

The `<TCEforms>` tag is no longer necessary (and allowed) in FlexForm
definitions. It was used to wrap TCA configuration and sheet
titles / descriptions. This wrapping must be omitted now.

Impact
======

Not omitting `TCEforms` will trigger a deprecation warning and log a message in
the deprecation log. An automatic migration is in place, which will eventually
be removed in upcoming TYPO3 versions.

Affected Installations
======================

*  All installations making use of FlexForm in their TCA. FlexForm definitions can
   be defined directly in TCA or can be external XML files.

*  Extensions, which extend the YAML configuration for forms with new fields.

Migration
=========

Omit the `<TCEforms>` tag in your FlexForm definition. The underlying
configuration moves one level up.

Before:

..  code-block:: xml

    <T3DataStructure>
        <ROOT>
            <TCEforms>
                <sheetTitle>sheet description 1</sheetTitle>
                <sheetDescription>
                    sheetDescription: Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                </sheetDescription>
                <sheetShortDescr>
                    sheetShortDescr: Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                </sheetShortDescr>
            </TCEforms>
            <type>array</type>
            <el>
                <input_1>
                    <TCEforms>
                        <label>input_1</label>
                        <config>
                            <type>input</type>
                        </config>
                    </TCEforms>
                </input_1>
            </el>
        </ROOT>
    </T3DataStructure>

After:

..  code-block:: xml

    <T3DataStructure>
        <ROOT>
            <sheetTitle>sheet description 1</sheetTitle>
            <sheetDescription>
                sheetDescription: Lorem ipsum dolor sit amet, consectetur adipiscing elit.
            </sheetDescription>
            <sheetShortDescr>
                sheetShortDescr: Lorem ipsum dolor sit amet, consectetur adipiscing elit.
            </sheetShortDescr>
            <type>array</type>
            <el>
                <input_1>
                    <label>input_1</label>
                    <config>
                        <type>input</type>
                    </config>
                </input_1>
            </el>
        </ROOT>
    </T3DataStructure>

Migration for form YAML configuration:

Before:

..  code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              finishersDefinition:
                EmailToReceiver:
                  FormEngine:
                    elements:
                      recipients:
                        el:
                          _arrayContainer:
                            el:
                              email:
                                TCEforms:
                                  label: tt_content.finishersDefinition.EmailToSender.recipients.email.label
                                  config:
                                    type: input

After:

..  code-block:: yaml

    TYPO3:
      CMS:
        Form:
          prototypes:
            standard:
              finishersDefinition:
                EmailToReceiver:
                  FormEngine:
                    elements:
                      recipients:
                        el:
                          _arrayContainer:
                            el:
                              email:
                                label: tt_content.finishersDefinition.EmailToSender.recipients.email.label
                                config:
                                  type: input

.. index:: FlexForm, TCA, NotScanned, ext:core
