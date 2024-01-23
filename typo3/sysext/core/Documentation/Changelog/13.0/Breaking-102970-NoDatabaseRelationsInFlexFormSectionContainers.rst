.. include:: /Includes.rst.txt

.. _breaking-102970-1706447911:

========================================================================
Breaking: #102970 - No database relations in FlexForm container sections
========================================================================

See :issue:`102970`

Description
===========

FlexForm handling details can be troublesome in certain scenarios. The Core
suffers from some nasty issues in this area, especially when relations
to other tables are used in FlexForms - the system for instance tends to mix
up things with language and workspace on this level.

The Core strives to get these scenarios sorted out, and a couple of patches to
prepare towards better flex form handling have been done with v13.0 already.

To unblock further development in this area, one detail is restricted a bit more
than with previous versions: FlexForm container section data structures must no
longer contain fields that configure relations to other database tables.

This has already been restricted since TYPO3 v8 for TCA :php:`type="inline"` and
has been partially extended to :php:`type="category"` and others later, if they
configured :php:`MM` relations in FlexForm sections containers. Now, especially
:php:`type="select"` with :php:`foreign_table` will also throw an exception.

In general, anonymous FlexForm container section data can and should not point to
database entities. Their use is tailored for "simple" types like :php:`input`,
:php:`email` and similar, support of those will not be restricted.

Note this does *not* restrict using casual FlexForms without containers sections,
like FlexForm data structures that rely on casual fields in sheets: Those can
continue to work with TCA types like :php:`inline`, :php:`group` and :php:`select`,
and the Core development tries to actively fix existing problematic scenarios
in this area.


Impact
======

When editing records that configure FlexForms with container sections that use
database relation-aware :php:`TCA` types, an exception will be thrown by
FormEngine. The related code may later be relocated to a lower level place
that can be triggered by DataHandler as well.


Affected installations
======================

Instances with extensions that use FlexForm container sections configuring
database relations to tables.

Since previous core versions restricted database relations within FlexForm
container sections already, and since container sections are a relatively rarely
used feature in the first place, we don't expect too many extensions to be
affected by this.

You can easily spot custom usage of FlexForm sections by searching for a :xml:`<section>`
tag within your FlexForm :file:`.xml` files, or within a TCA definition
with :php:`type="flex"`. These will be the instances you need to migrate,
when those sections contain :php:`type="select"` fields (or others mentioned above).

Affected FlexForm XML
---------------------

..  code-block:: xml
    :caption: EXT:my_extension/Configuration/FlexForms/Example.xml
    :emphasize-lines: 15-38

    <?xml version="1.0" encoding="utf-8" standalone="yes" ?>
    <T3DataStructure>
        <sheets>
            <sSection>
                <ROOT>
                    <sheetTitle>section</sheetTitle>
                    <type>array</type>
                    <el>
                        <section_1>
                            <title>section_1</title>
                            <type>array</type>
                            <!-- this is what to look out for: -->
                            <section>1</section>
                            <el>
                                <container_1>
                                    <type>array</type>
                                    <title>container_1</title>
                                    <el>
                                        <select_tree_1>
                                            <label>select_tree_1 pages description</label>
                                            <description>field description</description>
                                            <config>
                                                <type>select</type>
                                                <renderType>selectTree</renderType>
                                                <foreign_table>pages</foreign_table>
                                                <foreign_table_where>ORDER BY pages.sorting</foreign_table_where>
                                                <size>20</size>
                                                <treeConfig>
                                                    <parentField>pid</parentField>
                                                    <appearance>
                                                        <expandAll>true</expandAll>
                                                        <showHeader>true</showHeader>
                                                    </appearance>
                                                </treeConfig>
                                            </config>
                                        </select_tree_1>
                                    </el>
                                </container_1>
                            </el>
                        </section_1>
                    </el>
                </ROOT>
            </sSection>
        </sheets>
    </T3DataStructure>


Affected FlexForm TCA
---------------------

..  code-block:: php
    :caption: EXT:my_extension/Configuration/TCA/tx_myextension_flex.php
    :emphasize-lines: 22-45

    [
        'columns' => [
            'flex_2' => [
                'label' => 'flex section container',
                'config' => [
                    'type' => 'flex',
                    'ds' => [
                        'default' => '
                            <T3DataStructure>
                                <sheets>
                                    <sSection>
                                        <ROOT>
                                            <sheetTitle>section</sheetTitle>
                                            <type>array</type>
                                            <el>
                                                <section_1>
                                                    <title>section_1</title>
                                                    <type>array</type>
                                                    <!-- this is what to look out for: -->
                                                    <section>1</section>
                                                    <el>
                                                        <container_1>
                                                            <type>array</type>
                                                            <title>container_1</title>
                                                            <el>
                                                                <select_tree_1>
                                                                    <label>select_tree_1 pages description</label>
                                                                    <description>field description</description>
                                                                    <config>
                                                                        <type>select</type>
                                                                        <renderType>selectTree</renderType>
                                                                        <foreign_table>pages</foreign_table>
                                                                        <foreign_table_where>ORDER BY pages.sorting</foreign_table_where>
                                                                        <size>20</size>
                                                                        <treeConfig>
                                                                            <parentField>pid</parentField>
                                                                            <appearance>
                                                                                <expandAll>true</expandAll>
                                                                                <showHeader>true</showHeader>
                                                                            </appearance>
                                                                        </treeConfig>
                                                                    </config>
                                                                </select_tree_1>
                                                            </el>
                                                        </container_1>
                                                    </el>
                                                </section_1>
                                            </el>
                                        </ROOT>
                                    </sSection>
                                </sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ],
    ]

Migration
=========

Some extensions tried to work around existing restrictions by switching from
:php:`type="inline"` to :php:`type="group"` or :php:`type="select"`, ending
up with the same problematic scenario.

The basic issue is still, that binding database entities to anonymous data
structures is a problematic approach in the first place: A container section
that can be repeated often, combined with the additional built-in feature to
have multiple different sections at the same time, is close to impossible to
manage in a way that does not easily destroy data integrity.

Extensions that rely on this feature need to get rid of this approach: It
typically means rewriting the extension to model relations using :php:`type="inline"`
bound to database columns directly.


.. index:: Backend, FlexForm, TCA, NotScanned, ext:core
