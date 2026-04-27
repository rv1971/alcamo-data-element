<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\OutOfRange;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{LiteralInterface, PositiveGYearLiteral};
use alcamo\time\PosixFormat;

/**
 * @brief (De)Serializer for date/time data
 *
 * @date Last reviewed 2026-04-21
 */
class DateTimeSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' dateTime',
        self::XSD_NS . ' date',
        self::XSD_NS . ' gDay',
        self::XSD_NS . ' gMonth',
        self::XSD_NS . ' gMonthDay',
        self::XSD_NS . ' gYear',
        self::XSD_NS . ' gYearMonth',
        self::XSD_NS . ' time',
    ];

    public const ENCODINGS = [
        'ASCII'  => [ 8, ' ' ],
        'BCD'    => [ 4, 'F' ],
        'EBCDIC' => [ 8, "\x40" ]
    ];

    public const DEFAULT_POSIX_FORMATS = [
        self::XSD_NS . ' date' => [
            'BCD' => '%Y%m%d',
            '*'   => '%Y-%m-%d'
        ],
        self::XSD_NS . ' dateTime' => [
            'BCD' => '%Y%m%d%H%M%S',
            '*'   => '%Y-%m-%dT%H-%M-%S'
        ],
        self::XSD_NS . ' gDay' => [
            '*' => '%d'
        ],
        self::XSD_NS . ' gMonth' => [
            '*' => '%m'
        ],
        self::XSD_NS . ' gMonthDay' => [
            'BCD' => '%m%d',
            '*'   => '%m-%d'
        ],
        self::XSD_NS . ' gYearMonth' => [
            'BCD' => '%Y%m',
            '*'   => '%Y-%m'
        ],
        self::XSD_NS . ' gYear' => [
            '*' => '%Y'
        ],
        self::XSD_NS . ' time' => [
            'BCD' => '%H%M%S',
            '*'   => '%H-%M-%S'
        ]
    ];

    public static function newFromProps($props): SerializerInterface
    {
        $props = (object)$props;

        return new static(
            $props->datatypeXName ?? null,
            $props->encoding ?? null,
            $props->posixFormat ?? null,
            $props->flags ?? null,
            $props->padString ?? null,
            $props->padType ?? null,
            $props->literalWorkbench ?? null
        );
    }

    private $posixFormat_; ///< PosixFormat

    /**
     * @param $datatypeXName Datatype for deserialized literals [default first
     * item in SUPPORTED_DATATYPE_XNAMES)
     *
     * @parm $encoding [default first key of
     * alcamo::data_element::AbstractSerializer::ENCODINGS]
     *
     * @param $posixFormat POSIX format for input/output. Length is fixed and
     * computed from $posixFormat. [default taken from DEFAULT_POSIX_FORMATS]
     *
     * @param $flags Bitwise-OR-combination of the constants in
     * alcamo::data_element::SerializerInterface.
     *
     * @param $padString Padding string. [default taken from from
     * alcamo::data_element::AbstractSerializer::ENCODINGS]
     *
     * @param $padType STR_PAD_RIGHT or STR_PAD_LEFT. Truncation, if
     * necessary, takes place on the same side as padding. [default
     * alcamo::data_element::AbstractSerializer::PAD_TYPE]
     *
     * @param $literalWorkbench Workbench used in deserialize() and in
     * validateLiteralClass(). [default
     * alcamo::data_element::LiteralWorkbench::getMainInstance()]
     */
    public function __construct(
        ?string $datatypeXName = null,
        ?string $encoding = null,
        $posixFormat = null,
        ?int $flags = null,
        ?string $padString = null,
        ?int $padType = null,
        ?LiteralWorkbench $literalWorkbench = null
    ) {
        /* No padding will take place since the output strings are created at
         * the exact length of the chosen format (which may contain padding
         * characters if needed). */
        parent::__construct(
            $datatypeXName,
            $encoding,
            null,
            $flags,
            $padString,
            $padType,
            $literalWorkbench
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

        /* The length of input is validated if the chosen format has a fixed
         * length. */

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

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if (static::ENCODINGS[$this->encoding_][0] == 4) {
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

        return $this->literalWorkbench_->createLiteral(
            \DateTime::createFromFormat(
                $this->posixFormat_->getPhpFormat(),
                $value
            ),
            $datatype ?? $this->datatype_
        );
    }
}
