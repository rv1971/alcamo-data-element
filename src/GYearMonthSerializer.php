<?php

namespace alcamo\data_element;

use alcamo\rdfa\GYearMonthLiteral;

class GYearMonthSerializer extends AbstractGYearMonthTimeSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [ [ self::XSD_NS, 'gYearMonth' ] ];

    public const DEFAULT_DATATYPE_URI = GYearMonthLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [ GYearMonthLiteral::class ];

    public const DEFAULT_POSIX_FORMATS = [
        'BCD' => '%Y%m',
        '*'   => '%Y-%m'
    ];
}
