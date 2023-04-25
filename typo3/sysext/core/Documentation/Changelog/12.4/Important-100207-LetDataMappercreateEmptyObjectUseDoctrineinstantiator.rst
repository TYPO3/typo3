.. include:: /Includes.rst.txt

.. _important-100207-1679414752:

==================================================================================
Important: #100207 - Let DataMapper::createEmptyObject() use doctrine/instantiator
==================================================================================

See :issue:`100207`

Description
===========

Introduction
------------

This document explains the intended way in which the Extbase ORM thaws/hydrates objects.

Hydrating objects
-----------------

Hydrating (the term originates from `doctrine/orm`), or in Extbase terms thawing, is
the act of creating an object from a given database row. The responsible class involved
is the :php:`DataMapper`. During the process of hydrating, the :php:`DataMapper` creates
objects to map the raw database data onto.

Before diving into the framework internals, let's take a look at models from the
user's perspective.

Creating objects with constructor arguments
-------------------------------------------

Imagine you have a table :sql:`tx_extension_domain_model_blog` and a corresponding model
or entity (entity is used as a synonym here) :php:`Vendor\Extension\Domain\Model\Blog`.

Now, also imagine there is a domain rule which states, that all blogs must have a
title. This rule can easily be followed by letting the blog class have a constructor
with a required argument :php:`string $title`.

..  code-block:: php

    class Blog extends AbstractEntity
    {
        protected ObjectStorage $posts;

        public function __construct(protected string $title)
        {
            $this->posts = new ObjectStorage();
        }
    }

This example also shows how the :php:`posts` property is initialized. It is done in
the constructor because PHP does not allow setting a default value that is of
type object.

Hydrating objects with constructor arguments
--------------------------------------------

Whenever the user creates new blog objects in extension code, the aforementioned
domain rule is followed. It is also possible to work on the :php:`posts` :php:`ObjectStorage`
without further initialization. :php:`new Blog('title')` is all I need to create
a blog object with a valid state.

What happens in the :php:`DataMapper` however, is a totally different thing. When
hydrating an object, the :php:`DataMapper` cannot follow any domain rules. Its only
job is to map the raw database values onto a `Blog` instance. The :php:`DataMapper`
could of course detect constructor arguments and try to guess which argument
corresponds to what property but only if there is an easy mapping, i.e. if the
constructor takes argument :php:`string $title` and updates property `title` with it.

To avoid possible errors due to guessing, the :php:`DataMapper` simply
ignores the constructor at all. It does so with the help of the library `doctrine/instantiator`_.

..  _doctrine/instantiator: https://github.com/doctrine/instantiator

This pretty much explains the title of this document in detail. But there is more
to all this.

Initializing objects
--------------------

Have a look at the :php:`$posts` property in the example above. If the :php:`DataMapper`
ignores the constructor, that property is in an invalid state, i.e. uninitialized.

To address this problem and possible others, the :php:`DataMapper` will call the method
`initializeObject(): void` on models, if it exists.

Here is an updated version of the model:

..  code-block:: php

    class Blog extends AbstractEntity
    {
        protected ObjectStorage $posts;

        public function __construct(protected string $title)
        {
            $this->initializeObject();
        }

        public function initializeObject(): void
        {
            $this->posts = new ObjectStorage();
        }
    }

This example demonstrates how Extbase expects the user to set up their model(s). If
method :php:`initializeObject()` is used for initialization logic that needs to be
triggered on initial creation AND on hydration. Please mind that :php:`__construct()`
**SHOULD** call :php:`initializeObject()`.

If there are no domain rules to follow, the recommended way to set up a model
would then still be to define a :php:`__construct()` and :php:`initializeObject()`
method like this:

..  code-block:: php

    class Blog extends AbstractEntity
    {
        protected ObjectStorage $posts;

        public function __construct()
        {
            $this->initializeObject();
        }

        public function initializeObject(): void
        {
            $this->posts = new ObjectStorage();
        }
    }

Mutating objects
----------------

I'd like to add a few more words on mutators (setter, adder, etc.). One might think that
:php:`DataMapper` uses mutators during object hydration but it DOES NOT. `mutators`
are the only way for the user (developer) to implement business rules besides
using the constructor.

The :php:`DataMapper` uses the `@internal` method :php:`AbstractDomainObject::_setProperty()`
to update object properties. This looks a bit dirty and is a way around all business
rules but that's what the :php:`DataMapper` needs in order to leave the `mutators` to
the users.

..  warning::

    While :php:`DataMapper` does not use any mutators, other parts of Extbase do.
    Both, validation and property mapping, either use existing mutators or gather
    type information from them. This will change in the future but as of TYPO3 v12 LTS
    this information is correct.

Property visibility
-------------------

One important thing to know is that Extbase needs entity properties to be protected
or public. As written in the former paragraph, :php:`AbstractDomainObject::_setProperty()`
is used to bypass setters. :php:`AbstractDomainObject` however, is not able to access
private properties of child classes, hence the need to have protected or public
properties.


Dependency injection
--------------------

Without digging too deep into this topic the following statements have to be made.
Extbase expects entities to be so called prototypes, i.e. classes that do have a
different state per instance. DataMapper DOES NOT use dependency injection for the
creation of entities, i.e. it does not query the object container. This also means,
that dependency injection is not possible in entities.

If you think that your entities need to use/access services, you need to find other
ways to implement it.

.. index:: PHP-API, ext:extbase
