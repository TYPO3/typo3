..  include:: /Includes.rst.txt

..  _important-35069-1787591772:

==========================================================================
Important: #35069 - addQueryString.exclude also applies to config.linkVars
==========================================================================

See :issue:`35069`

Description
===========

Both :typoscript:`addQueryString` and :typoscript:`config.linkVars` carry query
parameters of the current request over into generated page links. Until now
:typoscript:`addQueryString.exclude` only removed a parameter from the former,
so a parameter listed in :typoscript:`config.linkVars` was silently added back
to the link and could not be dropped at all:

..  code-block:: typoscript

    config.linkVars = print(int)

    lib.link = TEXT
    lib.link {
        value = A link without the print parameter
        typolink {
            parameter.data = page:uid
            addQueryString = untrusted
            addQueryString.exclude = print
        }
    }

With a request to :samp:`/some-page?print=1` the generated link still contained
:samp:`?print=1`.

:typoscript:`addQueryString.exclude` is now honored for parameters contributed
by :typoscript:`config.linkVars` as well, so the link above no longer carries
the :samp:`print` parameter. The exclusion is only applied when
:typoscript:`addQueryString` is active — :typoscript:`config.linkVars` on its
own is unaffected.

Integrators who relied on :typoscript:`config.linkVars` overruling
:typoscript:`addQueryString.exclude` need to remove the affected parameter from
the :typoscript:`exclude` list.

..  index:: Frontend, TypoScript, NotScanned, ext:frontend
