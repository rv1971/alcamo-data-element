<?php

namespace alcamo\data_element;

use alcamo\exception\OutOfRange;
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{
    DateLiteral,
    DateTimeLiteral,
    GDayLiteral,
    GMonthLiteral,
    GMonthDayLiteral,
    GYearMonthLiteral,
    LiteralInterface,
    PositiveGYearLiteral,
    TimeLiteral
};
use alcamo\time\PosixFormat;

/**
 * @brief (De)Serializer for date/time data
 *
 * @date Last reviewed 2026-02-24
 */
class DateTimeSerializer extends AbstractSerializerWithEncoding
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        [ self::XSD_NS, 'date' ],
        [ self::XSD_NS, 'dateTime' ],
        [ self::XSD_NS, 'gDay' ],
        [ self::XSD_NS, 'gMonth' ],
        [ self::XSD_NS, 'gMonthDay' ],
        [ self::XSD_NS, 'gYear' ],
        [ self::XSD_NS, 'gYearMonth' ],
        [ self::XSD_NS, 'time' ]
    ];

    public const DEFAULT_DATATYPE_URI = DateTimeLiteral::DATATYPE_URI;

    public const ENCODINGS_TO_BITS =
        [ 'ASCII' => 8, 'BCD' => 4, 'EBCDIC' => 8 ];

    public const DEFAULT_ENCODING = 'ASCII';

    public const DEFAULT_POSIX_FORMATS = [
        self::XSD_NS . ' date'  => [
            'BCD' => '%Y%m%d',
            '*'   => '%Y-%m-%d'
        ],
        self::XSD_NS . ' dateTime'  => [
            'BCD' => '%Y%m%d%H%M%S',
            '*'   => '%Y-%m-%dT%H-%M-%S'
        ],
        self::XSD_NS . ' gDay'   => [ '*' => '%d' ],
        self::XSD_NS . ' gMonth' => [ '*' => '%m' ],
        self::XSD_NS . ' gMonthDay'  => [
            'BCD' => '%m%d',
            '*'   => '%m-%d'
        ],
        self::XSD_NS . ' gYearMonth'  => [
            'BCD' => '%Y%m',
            '*'   => '%Y-%m'
        ],
        PositiveGYearLiteral::DATATYPE_XNAME[0] . ' '
            . PositiveGYearLiteral::DATATYPE_XNAME[1] => [ '*' => '%Y' ],
        self::XSD_NS . ' time'  => [
            'BCD' => '%H%M%S',
            '*'   => '%H-%M-%S'
        ],
    ];

    private $posixFormat_; ///< PosixFormat

    public function __construct(
        ?DataElementInterface $dataElement = null,
        $posixFormat = null,
        ?int $flags = null,
        ?string $encoding = null,
        ?LiteralFactory $literalFactory = null,
        ?LiteralTypeMap $literalTypeMap = null
    ) {
        parent::__construct(
            $dataElement,
            null,
            $flags,
            $encoding,
            $literalFactory,
            $literalTypeMap
        );

        if (isset($posixFormat)) {
            $this->posixFormat_ = $posixFormat instanceof PosixFormat
                ? $posixFormat
                : new PosixFormat($posixFormat);
        } else {
            $supportedDatatypeXName =
                (string)$this->supportedDatatype_->getXName();

            $this->posixFormat_ = new PosixFormat(
                static::DEFAULT_POSIX_FORMATS[$supportedDatatypeXName][
                    $this->encoding_
                ]
                ?? static::DEFAULT_POSIX_FORMATS[$supportedDatatypeXName]['*']
            );
        }

        $length = $this->posixFormat_->getLength();

        if (isset($length)) {
            $this->lengthRange_ = new NonNegativeRange($length, $length);
        }
    }

    public function getPosixFormat(): PosixFormat
    {
        return $this->posixFormat_;
    }

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        $value = $this->posixFormat_->applyTo($literal->getValue());

        switch ($this->encoding_) {
            case 'ASCII':
                return $value;

            case 'BCD':
                /** @throw alcamo::exception::OutOfRange if encoding is BCD
                 *  and the date is negative. */
                OutOfRange::throwIfNegative($literal->format('Y'));

                return hex2bin($value);

            case 'EBCDIC':
                return strtr(
                    $value,
                    '-0123456789:T',
                    "\x60\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\x7A\xE3"
                );
        }
    }

    public function deserialize(string $input): LiteralInterface
    {
        if (static::ENCODINGS_TO_BITS[$this->encoding_] == 4) {
            $input = bin2hex($input);
        }

        $this->validateInputLength($input);

        switch ($this->encoding_) {
            case 'ASCII':
            case 'BCD':
                $value = $input;
                break;

            case 'EBCDIC':
                $value = strtr(
                    $input,
                    "\x60\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\x7A\xE3",
                    '-0123456789:T'
                );
                break;
        }

        return $this->literalFactory_->createLiteralForDataElement(
            $this->dataElement_,
            \DateTime::createFromFormat(
                $this->posixFormat_->getPhpFormat(),
                $value
            )
        );
    }
}
