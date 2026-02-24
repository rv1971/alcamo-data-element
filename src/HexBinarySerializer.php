<?php

namespace alcamo\data_element;

use alcamo\rdfa\HexBinaryLiteral;

/**
 * @brief (De)Serializer for hex binary data
 *
 * @date Last reviewed 2026-02-24
 */
class HexBinarySerializer extends AbstractBinarySerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [ [ self::XSD_NS, 'hexBinary' ] ];

    public const DEFAULT_DATATYPE_URI = HexBinaryLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [ HexBinaryLiteral::class ];
}
