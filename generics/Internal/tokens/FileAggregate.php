<?php declare(strict_types=1);

namespace grikdotnet\generics\Internal\tokens;

/**
 * This is a DTO to pass the result of parsing source code
 */
readonly class FileAggregate {

    private const FILENAME = 1;
    private const CLASSES = 2;
    private const INSTANTIATIONS = 3;

    public function __construct(

        public string $path,
        /**
         * @var array<class-string, ClassAggregate>
         */
        public array $classAggregates = [],
        /**
         * @var array<ConcreteInstantiationToken>
         */
        public array $instantiations = []
    )
    {}

    public function isEmpty(): bool
    {
        return $this->classAggregates === [] && $this->instantiations === [];
    }

    /**
     * Serialize data for opcache
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::FILENAME => $this->path,
            self::CLASSES => array_map(fn(ClassAggregate $c)=>$c->toArray(), $this->classAggregates),
            self::INSTANTIATIONS => array_map(fn(ConcreteInstantiationToken $i)=>array_values((array)$i), $this->instantiations)
        ];
    }

    /**
     * Restore data from cache
     * @param array $cache
     * @return self
     */
    public static function fromArray(array $cache): self
    {
        $instantiations = $classes = [];
        foreach ($cache[self::INSTANTIATIONS] as $i) {
            $instantiations[] = new ConcreteInstantiationToken(...$i);
        }
        foreach ($cache[self::CLASSES] as $c) {
            $classes[] = ClassAggregate::fromArray($c);
        }
        return new self($cache[self::FILENAME],$classes,$instantiations);
    }
}