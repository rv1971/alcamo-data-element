<?php

namespace alcamo\data_element;

use alcamo\rdfa\LiteralInterface;
use alcamo\xml\NamespaceConstantsInterface;

interface SerializerInterface extends NamespaceConstantsInterface
{
    /// Whether to truncate silently upon serialization if needed
    public const TRUNCATE_SILENTLY = 1;

    /// Do not check length upon deserialization
    public const SKIP_LENGTH_CHECK = 2;

    public function getDataElement(): DataElementInterface;

    public function getExtentRange(): ?ExtentRange;

    public function getFlags(): int;

    public function serialize(?LiteralInterface $literal = null): string;

    public function deserialize(string $input): LiteralInterface;
}
