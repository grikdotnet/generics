<?php declare(strict_types=1);

namespace Generics;

/**
 * An attribute declaration for IDE autocomplete
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY | \Attribute::TARGET_FUNCTION )]
class T {}
