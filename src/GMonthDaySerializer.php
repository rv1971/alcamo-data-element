<?php

namespace alcamo\data_element;

use alcamo\rdfa\GMonthDayLiteral;

class GMonthDaySerializer extends AbstractGMonthDayTimeSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [ [ self::XSD_NS, 'gMonthDay' ] ];

    public const DEFAULT_DATATYPE_URI = GMonthDayLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [ GMonthDayLiteral::class ];

    public const DEFAULT_POSIX_FORMATS = [
        'BCD' => '%m%d',
        '*'   => '%m-%d'
    ];
}
