<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdfa\LiteralInterface;

class DateTimeSerializer extends AbstractSerializerWithEncoding
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        [ self::XSD_NS, 'date' ],
        [ self::XSD_NS, 'dateTime' ],
        [ self::XSD_NS, 'gMonthDay' ],
        [ self::XSD_NS, 'gYearMonth' ],
        [ self::XSD_NS, 'time' ]
    ];

    public const DEFAULT_DATATYPE_URI = DateTimeLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [
        DateLiteral::class,
        DateTimeLiteral::class,
        GMonthDayLiteral::class,
        GYearMonthLiteral::class,
        TimeLiteral::class
    ];

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
        self::XSD_NS . ' gMonthDay'  => [
            'BCD' => '%m%d',
            '*'   => '%m-%d'
        ],
        self::XSD_NS . ' gYearMonth'  => [
            'BCD' => '%Y%m',
            '*'   => '%Y-%m'
        ],
        self::XSD_NS . ' time'  => [
            'BCD' => '%H%M%S',
            '*'   => '%H-%M-%S'
        ],
    ];

    /// Map of Posix format specifiers to text of appropriate length
    public const POSIX_FORMAT_SPECS_TO_TEXT = [
        '%Y' => 'YYYY',
        '%m' => 'mm',
        '%y' => 'yy',
        '%d' => 'dd',
        '%H' => 'HH',
        '%M' => 'ii',
        '%S' => 'ss'
    ];

    /// Map of Posix format specifiers to PHP format specifiers
    public const POSIX_FORMAT_SPECS_TO_PHP_FORMAT_SPECS = [
        '%Y' => 'Y',
        '%m' => 'm',
        '%y' => 'y',
        '%d' => 'd',
        '%H' => 'H',
        '%M' => 'i',
        '%S' => 's'
    ];

    private $posixFormat_; ///< string
    private $phpFormat_;   ///< string

    public function __construct(
        ?DataElementInterface $dataElement = null,
        ?string $posixFormat = null,
        ?int $flags = null,
        ?string $encoding = null,
        ?LiteralFactory $literalFactory = null
    ) {
        parent::__construct(
            $dataElement,
            null,
            $flags,
            $encoding,
            $literalFactory
        );

        $supportedDatatypeXName = (string)$this->supportedDatatype_->getXName();

        $this->posixFormat_ = $posixFormat
            ?? static::DEFAULT_POSIX_FORMATS[$supportedDatatypeXName][$this->encoding_]
            ?? static::DEFAULT_POSIX_FORMATS[$supportedDatatypeXName]['*'];

        $this->phpFormat_ = strtr(
            $this->posixFormat_,
            static::POSIX_FORMAT_SPECS_TO_PHP_FORMAT_SPECS
        );

        $length = strlen(
            strtr($this->posixFormat_, static::POSIX_FORMAT_SPECS_TO_TEXT)
        );

        $this->lengthRange_ = new NonNegativeRange($length, $length);
    }

    public function serialize(LiteralInterface): string
    {
        $this->validateLiteralClass($literal);

        $value = $literal->format($this->phpFormat_);

        switch ($this->encoding_) {
            case 'ASCII':
                return $value;

            case 'BCD':
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
        if ($this->encoding_ == 'BCD') {
            $input = bin2hex($input);
        }

        $this->validateInputLength($input);

        switch ($this->encoding_) {
            case 'ASCII':
                $value = $input;
                break;

            case 'BCD':
                $value = bin2hex($input);
                break;

            case 'EBCDIC':
                $value = strtr(
                    $input,
                    "\x60\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\x7A\xE3",
                    '-0123456789:T'
                );
                break;
        }

        $literalClass = static::SUPPORTED_LITERAL_CLASSES[0];

        return new $literalClass(
            DateTime::createFromFormat($this->phpFormat_, $value),
            $this->dataElement_->getDatatype()->getUri()
        );
    }
}
