<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdfa\LiteralInterface;
use alcamo\xml\NamespaceConstantsInterface;

/**
 * @brief (De)Serializer based on data element information
 *
 * @date Last reviewed 2026-02-24
 */
interface SerializerInterface extends NamespaceConstantsInterface
{
    /// Whether to truncate silently upon serialization if needed
    public const TRUNCATE_SILENTLY = 1;

    /// Do not check length upon deserialization
    public const SKIP_LENGTH_CHECK = 2;

    public function getDataElement(): DataElementInterface;

    public function getLengthRange(): ?NonNegativeRange;

    public function getFlags(): int;

    public function serialize(LiteralInterface $literal): string;

    public function deserialize(string $input): LiteralInterface;
}
