<?php

namespace alcamo\data_element;

use alcamo\rdfa\Base64BinaryLiteral;

/**
 * @brief (De)Serializer for base64 binary data
 *
 * @date Last reviewed 2026-02-24
 */
class Base64BinarySerializer extends AbstractBinarySerializer
{
    public const SUPPORTED_DATATYPE_XNAMES =
        [ [ self::XSD_NS, 'base64Binary' ] ];

    public const DEFAULT_DATATYPE_URI = Base64BinaryLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [ Base64BinaryLiteral::class ];
}
