.. include:: ../../Includes.txt


.. _concepts-introduction:

Target groups and main principles
=================================

As :ref:`mentioned earlier<what-does-it-do>`, the ``form`` extension can be
seen as a framework which allows editors, integrators, and developers to
create and manage all kind of forms. For this task, different interfaces
and techniques are available.

Conceptually, EXT:form always tries to consider the ``form editor`` first.
The requirements for the ``form editor`` differ between the defined target
groups. On the one hand, as an integrator, you may want to manage HTML
class attributes. On the other hand, as a developer you may want to use the
``form editor`` as a kick starter for complex ``form definitions``, and you
may want to edit all possible (technical) properties you can think of.

The form extension tries to find a compromise for such cases. Since the
``form editor`` is mainly used by backend editors, only simple,
nontechnical properties are displayed and editable. However, EXT:form
allows you to easily extend the ``form editor`` by writing some YAML
configurations.

If this is not enough for your specific project, EXT:form provides a way to
integrate your own JavaScript code by utilizing the JavaScript API. Thus,
it should be possible to meet all your requirements.

Your forms can be created and defined globally in the ``form module`` and/
or loaded from extensions. Within the ``Mail form`` content element, one of
those forms can be referenced.

Furthermore, certain aspects of a form can be overridden in the plugin. This
concept allows you to reuse the same form on different pages with the same,
or a different, configuration.

The following explanations will show you that there are many ways to
manipulate the form framework in different contexts.

Those explanations are partly contradictory, depending on your use case. It
is up to you how you want to use the form framework. Be creative and share
your solution with the TYPO3 community!

This chapter attempts to describe the basics of the form framework. Check
out the reference and the example sections to get a deeper understanding of
the framework.
