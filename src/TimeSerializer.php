<?php

namespace alcamo\data_element;

use alcamo\rdfa\TimeLiteral;

class TimeSerializer extends AbstractTimeSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [ [ self::XSD_NS, 'dateTime' ] ];

    public const DEFAULT_DATATYPE_URI = TimeLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [ TimeLiteral::class ];

    public const DEFAULT_POSIX_FORMATS = [
        'BCD' => '%H%M%S',
        '*'   => '%H:%M:%S'
    ];
}
