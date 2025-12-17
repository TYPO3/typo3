.. include:: /Includes.rst.txt


.. _concepts-introduction:

Target groups and main principles
=================================

As :ref:`we saw in the introduction<what-does-it-do>`, the ``form`` extension is a
framework where editors, integrators, and developers can
create and manage forms with different interfaces and functionality.

The most important part of EXT:form is the backend ``form editor``. Different types of users
can use  the ``form editor`` for different things. Integrators can manage HTML
class attributes, developers can create
complex ``form definitions`` and editors can edit properties.

The form extension tries to find a compromise between these things. The
``form editor`` is mainly designed for editors, so simple, easy-to-edit properties are
displayed. However, the ``form editor`` can be easily extended by YAML configuration.

And should this is not enough for your specific project, you can
integrate your own JavaScript code using the JavaScript API.

You can create and define forms globally in the :guilabel:`Web->Forms` module or you can load forms
from inside extensions, for example, the ``Mail form`` content element.

Some parts of a form can be overridden in the form plugin. This means you can
reuse the same form on different pages with a different configuration.

The information in this chapter will show you that there are many ways to
customize the form framework, depending on your use case. Be creative and share
your solution with the TYPO3 community!

This chapter describes the basics of the form framework. Check
out the reference and the examples to get a deeper understanding of
the framework.
