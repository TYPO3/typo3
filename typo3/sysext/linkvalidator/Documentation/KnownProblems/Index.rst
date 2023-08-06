.. include:: /Includes.rst.txt

.. _known-problems:

==============
Known problems
==============


.. _usagePitfallsExternalLinks:

Problems with external links
============================

The most relevant known problems currently concern "external broken links".

Be polite when crawling external sites: They should not be checked too often.
The extension currently provides no functionality to limit such requests.

There are (at least) 2 possible counter-measures:

#. Turn off external link checking entirely
   by removing `external` from Page TSconfig :ref:`linktypes`

#. :ref:`Override the ExternalLinktype class <linktype-implementation>`
   (in your own extension), to check only specific URLs or exclude specific
   URLs or handle only specific error types as errors.


.. _usagePitfallsFalsePositives:

Falsely reported broken links
=============================

Linkvalidator may find false negatives when checking links. This can be
annoying for editors since there is no way to declare them "ok" manually,
they will be shown to editors over and over again.

There are a couple of scenarios where linkvalidator may show a link as
broken leading to a misleading result:

*   Automatic server-side external link checking may fail. There are many possible
    reasons for false negatives in this area like server connectivity issues or
    funny deny rules on the target system.
*   Sometimes links may not be broken as such, but are considered "broken" due to
    HTTP status like 4xx.
*   The broken link information may be "stale", the link has been checked a while ago
    and did lead to a negative result due to temporary a system outage or similar, is
    now working again, but has not been rechecked yet.
