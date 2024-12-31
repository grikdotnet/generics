<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * A classification of the parameter types
 * @internal
 */
enum TypeType
{
    /** Denotes that the parameter should be treated as a template */
    case Template;
    /** The parameter has a type like int or user class */
    case Atomic;
    /** A composite type which is a combination of several types */
    case Union;
    /** (#[Generics\T(Foo)] fn() => new TemplateClass($x))(); */
    case Instance;
}
