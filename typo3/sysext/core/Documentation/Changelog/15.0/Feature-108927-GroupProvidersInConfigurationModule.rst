..  include:: /Includes.rst.txt

..  _feature-108927-1792218592:

==============================================================
Feature: #108927 - Group providers in the Configuration module
==============================================================

See :issue:`108927`

Description
===========

The dropdown of the :guilabel:`System > Configuration` module now shows the
configuration entries in groups, each with a heading, instead of one long
list. The groups provided by the system extension are:

*   System & Infrastructure
*   Data & Content
*   Backend & Users
*   Sites
*   HTTP & Security
*   Events & Integrations
*   Rendering & Frameworks
*   Extensions

Providers tagged with `lowlevel.configuration.module.provider` can choose a
group with the new optional tag attribute :yaml:`group`:

..  code-block:: yaml
    :caption: EXT:my_extension/Configuration/Services.yaml

    myextension.configuration.module.provider.myconfiguration:
        class: 'Vendor\Extension\ConfigurationModuleProvider\MyProvider'
        tags:
            - name: 'lowlevel.configuration.module.provider'
              identifier: 'myProvider'
              group: 'backend'

The keys for these groups are :yaml:`core`, :yaml:`data`, :yaml:`backend`,
:yaml:`sites`, :yaml:`requestHandling`, :yaml:`events`, :yaml:`frameworks`
and :yaml:`extensions`. Any other value is used as the label of a custom
group, either as a text or as a localization reference such as
:yaml:`LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:group`.
Custom groups are shown after the functional groups and before
:guilabel:`Extensions`. Providers without a :yaml:`group` attribute, with
an empty value or with the explicit value :yaml:`extensions` are shown in
the last group, :guilabel:`Extensions`. Empty groups are not displayed.

The attributes :yaml:`before` and :yaml:`after` continue to work and define
the order of providers within a group.

Impact
======

The Configuration module is easier to navigate. Existing providers keep
working without changes and are shown in the group
:guilabel:`Extensions`. Extension authors can opt in to a group
by adding the :yaml:`group` attribute.

..  index:: Backend, ext:lowlevel
