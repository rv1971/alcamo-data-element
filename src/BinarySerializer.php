<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\rdfa\LiteralInterface;

/**
 * @brief (De)Serializer for binary data
 *
 * @date Last reviewed 2026-02-24
 */
class BinarySerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' hexBinary',
        self::XSD_NS . ' base64Binary'
    ];

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        $value = $literal->getValue()->getData();

        return $this->adjustOutputLength(
            $value instanceof BinaryString ? $value->getData() : (string)$value,
            "\x00"
        );
    }

    public function deserialize(string $input): LiteralInterface
    {
        $this->validateInputLength($input);

        return $this->factoryGroup_->getLiteralFactory()->create(
            $this->datatype_,
            new BinaryString($input)
        );
    }
}
