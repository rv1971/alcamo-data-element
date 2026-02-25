<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\rdfa\{Base64BinaryLiteral, HexBinaryLiteral, LiteralInterface};

/**
 * @brief (De)Serializer for binary data
 *
 * @date Last reviewed 2026-02-24
 */
class BinarySerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        [ self::XSD_NS, 'base64Binary' ],
        [ self::XSD_NS, 'hexBinary' ]
    ];

    public const DEFAULT_DATATYPE_URI = HexBinaryLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [
        Base64BinaryLiteral::class,
        HexBinaryLiteral::class
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

        return $this->literalFactory_->createLiteralForDataElement(
            $this->dataElement_,
            new BinaryString($input)
        );
    }
}
