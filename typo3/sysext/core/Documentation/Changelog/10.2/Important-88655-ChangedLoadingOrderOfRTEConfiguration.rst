.. include:: /Includes.rst.txt

==============================================================
Important: #88655 - Changed loading order of RTE Configuration
==============================================================

See :issue:`88655`

Description
===========

The order in which RTE Configuration is loaded has been changed.

The new order is:

#. preset defined for a specific field via PageTS

#. richtextConfiguration defined for a specific field via TCA

#. general preset defined via PageTS

#. default

This results in a change if you were used to using :typoscript:`RTE.default.preset` to overwrite _all_ RTE
configuration presets - as those with specific configuration in TCA now use their specific settings
instead of falling back to the default. Please make sure, that this new behavior is fitting for your
use cases.

If you are an extension author and you want your RTE fields to use the systems default configuration
(the one configured for the complete web site) please do not set a specific preset for your fields.
If you as an extension author want to provide a specific preset - for example because you are
providing a custom parseFunc - set the property `richtextConfiguration` in TCA.

If an extension provides a custom preset for a specific field and you as an integrator want to
override that configuration (for example to use "your" default), set it specifically for that field
in TSConfig or overwrite the TCA configuration.

For example:

If the blog extension configures `'richtextConfiguration' => 'blog'` for the tag description and
you want the tag description to use the default preset, set
:typoscript:`RTE.config.tx_blog_domain_model_tag.content.types.text.preset = default`.

.. index:: RTE, TCA, TSConfig, ext:core
