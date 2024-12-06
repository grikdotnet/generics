<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * A classification of the parameter types
 */
enum TypeType
{
    /** Denotes that the parameter should be treated as a template */
    case Template;
    /** The parameter has a built-in type like int or array*/
    case Builtin;
    /** Parameter is a user defined type, i.e. class or interface */
    case UserDefined;
    /** A composite type which is a combination of several types */
    case Union;
}
