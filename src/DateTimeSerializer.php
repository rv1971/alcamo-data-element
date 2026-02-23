<?php

namespace alcamo\data_element;

use alcamo\rdfa\DateTimeLiteral;

class DateTimeSerializer extends AbstractDateTimeSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [ [ self::XSD_NS, 'dateTime' ] ];

    public const DEFAULT_DATATYPE_URI = DateTimeLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [ DateTimeLiteral::class ];

    public const DEFAULT_POSIX_FORMATS = [
        'BCD' => '%Y%m%d%H%M%S',
        '*'   => '%Y-%m-%dT%H:%M:%S'
    ];
}
