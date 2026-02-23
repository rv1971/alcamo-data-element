<?php

namespace alcamo\data_element;

use alcamo\rdfa\DateLiteral;

class DateSerializer extends AbstractDateTimeSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [ [ self::XSD_NS, 'date' ] ];

    public const DEFAULT_DATATYPE_URI = DateLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [ DateLiteral::class ];

    public const DEFAULT_POSIX_FORMATS = [
        'BCD' => '%Y%m%d',
        '*'   => '%Y-%m-%d'
    ];
}
