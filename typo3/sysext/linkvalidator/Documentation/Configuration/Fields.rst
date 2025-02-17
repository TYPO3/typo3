:navigation-title: Checked Fields

..  include:: /Includes.rst.txt
..  _checked-fields:

===================================
Fields checked by the Linkvalidator
===================================

..  versionchanged:: 13.3
    The following fields where added to the list of fields that are checked by
    default:

    *   :typoscript:`pages = canonical_link`
    *   :typoscript:`sys_redirect = target`
    *   :typoscript:`sys_file_reference = link`

The following tables and fields are supported by default:

*   :typoscript:`pages = url, canonical_link`
*   :typoscript:`sys_redirect = target`
*   :typoscript:`sys_file_reference = link`
*   :typoscript:`tt_content = bodytext, header_link`

Two special fields are currently defined, but are
not checked yet due to their TCA configuration:

*   :typoscript:`pages = media` has TCA `type="file"`
*   :typoscript:`tt_content = records` has TCA `type="group"`

The following fields could theoretically be included in
custom configurations, as their type / softref is available,
but they are specifically not added in the default configuration:

*   :typoscript:`sys_webhook = url` (webhook should not be invoked)
*   :typoscript:`tt_content = subheader` (has softref `email[subst]`
    which is not a supported link type)
*   :typoscript:`pages = tsconfig_includes` (system configuration)
*   :typoscript:`sys_template = constants, include_static_file, config`
    (system configuration)
*   :typoscript:`tx_scheduler_task_group = groupName` (scheduler
    system configuration)

..  _checked-fields-TCA:

Required TCA configuration so a field can be checked by the Linkvalidator
=========================================================================

Currently, LinkValidator will only detect links for fields if the
TCA configuration meets one of these criteria:

*   at least one :ref:`softref <t3tca:tca_property_softref>`
*   type is set to :ref:`link <t3tca:columns-link>`
*   type is set to :ref:`email <t3tca:columns-email>`

For this reason, it is currently not possible to check for
`pages.media`. This will be fixed in the future.

Examples for working fields:

*   `pages.canonical_link` (:php:`'type' => 'link'`)
*   `pages.url` (:php:`'softref' => 'url'`)
*   `sys_file_reference.link` (:php:`'type' => 'link'`)

Example for not working fields:

*   `pages.media` (:php:`'type' => 'file'`)
