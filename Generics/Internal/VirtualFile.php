<?php declare(strict_types=1);

namespace Generics\Internal;

readonly class VirtualFile {
    public function __construct(
        public string $path,
        public string $content,
        public ?string $reference_path = null
    ){}

    public function toArray(): array
    {
        return [$this->path,$this->content,$this->reference_path];
    }
}