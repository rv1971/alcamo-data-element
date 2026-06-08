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
        'ASCII'  => [ 8, ' ',    STR_PAD_RIGHT ],
        'BCD'    => [ 4, '0',    STR_PAD_LEFT ],
        'EBCDIC' => [ 8, "\x40", STR_PAD_RIGHT ]
    ];

    public const DEFAULT_POSIX_FORMATS = [
        self::XSD_NS . ' date' => [
            'BCD' => '%Y%m%d',
            '*'   => '%Y-%m-%d'
        ],
        self::XSD_NS . ' dateTime' => [
            'BCD' => '%Y%m%d%H%M%S',
            '*'   => '%Y-%m-%dT%H:%M:%S'
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
            '*'   => '%H:%M:%S'
        ]
    ];

    public static function newFromProps($props): SerializerInterface
    {
        $props = (object)$props;

        return new static(
            $props->datatypeXName ?? null,
            $props->encoding ?? null,
            $props->posixFormat ?? null,
            $props->asUtc ?? null,
            $props->flags ?? null,
            $props->padString ?? null,
            $props->padType ?? null,
            $props->deWorkbench ?? null
        );
    }

    private $posixFormat_;     ///< PosixFormat
    private $asUtc_;           ///< boolean

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
     * @param $deWorkbench Workbench used in deserialize() and in
     * validateLiteralClass(). [default
     * alcamo::data_element::DeWorkbench::getMainInstance()]
     */
    public function __construct(
        ?string $datatypeXName = null,
        ?string $encoding = null,
        $posixFormat = null,
        ?bool $asUtc = null,
        ?int $flags = null,
        ?string $padString = null,
        ?int $padType = null,
        ?DeWorkbench $deWorkbench = null
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
            $deWorkbench
        );

        $supportedDatatypeXName = (string)$this->supportedDatatype_->getXName();

        if (isset($posixFormat)) {
            $this->posixFormat_ = $posixFormat instanceof PosixFormat
                ? $posixFormat
                : new PosixFormat($posixFormat);
        } else {
            $this->posixFormat_ = new PosixFormat(
                static::DEFAULT_POSIX_FORMATS[$supportedDatatypeXName][
                    $this->encodingParams_->getEncoding()
                ]
                ?? static::DEFAULT_POSIX_FORMATS[$supportedDatatypeXName]['*']
            );
        }

        $this->asUtc_ = (bool)$asUtc;

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

    public function getAsUtc(): bool
    {
        return $this->asUtc_;
    }

    public function serialize(LiteralInterface $literal): string
    {
        switch ($this->encodingParams_->getEncoding()) {
            case 'ASCII':
                $this->validateLiteralClass($literal);

                return $this->posixFormat_
                    ->applyTo($this->getDateTime($literal));

            case 'BCD':
                return $this->hexToBin($this->serializeToHex($literal));

            case 'EBCDIC':
                $this->validateLiteralClass($literal);

                return strtr(
                    $this->posixFormat_
                        ->applyTo($this->getDateTime($literal)),
                    EncodingParams::ASCII_CHARS,
                    EncodingParams::EBCDIC_CHARS
                );
        }
    }

    public function serializeToHex(LiteralInterface $literal): string
    {
        switch ($this->encodingParams_->getEncoding()) {
            case 'BCD':
                $this->validateLiteralClass($literal);

                /** @throw alcamo::exception::OutOfRange if encoding is BCD
                 *  and the date is negative. */
                OutOfRange::throwIfNegative($literal->format('Y'));

                return $this->posixFormat_
                    ->applyTo($this->getDateTime($literal));

            default:
                return strtoupper(bin2hex($this->serialize($literal)));
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encodingParams_->getEncoding()) {
            case 'ASCII':
                $value = $this->preprocessInput($input);
                break;

            case 'BCD':
                return $this->deserializeFromHex(bin2hex($input), $datatype);

            case 'EBCDIC':
                $value = strtr(
                    $this->preprocessInput($input),
                    EncodingParams::EBCDIC_CHARS,
                    EncodingParams::ASCII_CHARS
                );
                break;
        }

        return $this->deWorkbench_->createLiteral(
            $this->asUtc_
                ? \DateTimeImmutable::createFromFormat(
                    $this->posixFormat_->getPhpFormat() . 'O',
                    "$value+0000"
                )
                : \DateTimeImmutable::createFromFormat(
                    $this->posixFormat_->getPhpFormat(),
                    $value
                ),
            $datatype ?? $this->datatype_
        );
    }

    public function deserializeFromHex(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encodingParams_->getEncoding()) {
            case 'BCD':
                $input = $this->preprocessInput($input);

                return $this->deWorkbench_->createLiteral(
                    $this->asUtc_
                        ? \DateTimeImmutable::createFromFormat(
                            $this->posixFormat_->getPhpFormat() . 'O',
                            "$input+0000"
                        )
                        : \DateTimeImmutable::createFromFormat(
                            $this->posixFormat_->getPhpFormat(),
                            $input
                        ),
                    $datatype ?? $this->datatype_
                );

            default:
                return $this->deserialize($this->hexToBin($input), $datatype);
        }
    }

    public function dump(LiteralInterface $literal): string
    {
        return (new Dumper())
            ->dumpString(
                $this->posixFormat_->applyTo($this->getDateTime($literal)),
                $this->encodingParams_->getEncoding()
            );
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $value = (new Dumper())
            ->dedumpString($input, $this->encodingParams_->getEncoding());

        return $this->deWorkbench_->createLiteral(
            $this->asUtc_
                ? \DateTimeImmutable::createFromFormat(
                    $this->posixFormat_->getPhpFormat() . 'O',
                    "$value+0000"
                )
                : \DateTimeImmutable::createFromFormat(
                    $this->posixFormat_->getPhpFormat(),
                    $value
                ),
            $datatype ?? $this->datatype_
        );
    }

    private function getDateTime(LiteralInterface $literal): \DateTimeImmutable
    {
        static $utcTimeZone;

        if (!isset($utcTimeZone)) {
            $utcTimeZone = new \DateTimeZone('UTC');
        }

        return $this->asUtc_
            ? $literal->getValue()->setTimezone($utcTimeZone)
            : $literal->getValue();
    }
}
