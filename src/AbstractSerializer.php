<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\{AbstractSimpleType, SimpleTypeInterface};
use alcamo\exception\{
    InvalidEnumerator,
    InvalidType,
    LengthOutOfRange,
    Unsupported
};
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief (De)Serializer for literal objects
 *
 * @date Last reviewed 2026-02-24
 */
abstract class AbstractSerializer implements SerializerInterface
{
    /**
     * @brief Extended name strings of supported data element datatypes
     *
     * The first item it taken as the default datatype.
     */
    public const SUPPORTED_DATATYPE_XNAMES = [];

    /**
     * @brief Supported encodings
     *
     * Assigns to each supported encoding a pair, consisting of
     * - the number of bits per encoded character
     * - the padding string.
     *
     * The encoding `*` represents all encodings not explicitely listed.
     *
     * If an encoding has an empty padding string, this means that the length
     * of a serialization result MUST NOT be changed, neither padded nor
     * truncated.
     */
    public const ENCODINGS = [ '*' => [ 8, ' ' ] ];

    /// Default encoding
    public const DEFAULT_ENCODING = 'ASCII';

    public const PAD_TYPE = STR_PAD_RIGHT;

    public static function newFromProps($props): SerializerInterface
    {
        $props = (object)$props;

        return new static(
            $props->datatypeXName ?? null,
            $props->encoding ?? null,
            $props->lengthRange ?? null,
            $props->flags ?? null,
            $props->padString ?? null,
            $props->padType ?? null,
            $props->literalWorkbench ?? null
        );
    }

    protected $datatype_; ///< SimpleTypeInterface

    /**
     * @brief SimpleTypeInterface
     *
     * That datatype listed in SUPPORTED_DATATYPE_XNAMES from which $datytpe_
     * is derived.
     */
    protected $supportedDatatype_;

    protected $encoding_;         ///< string
    protected $lengthRange_;      ///< ?NonNegativeRange
    protected $flags_;            ///< int
    protected $padString_;        ///< string
    protected $padType_;          ///< one of STR_PAD_RIGHT or STR_PAD_LEFT
    protected $literalWorkbench_; ///< LiteralWorkbench

    /**
     * @param $datatypeXName Datatype to use for deserialized literals
     * [default first item in SUPPORTED_DATATYPE_XNAMES]
     *
     * @parm $encoding [default
     * alcamo::data_element::AbstractSerializer::DEFAULT_ENCODING]
     *
     * @param $lengthRange NonNegativeRange|array Allowed length of serialized
     * data, in encoding-dependent units (bytes or nibbles). If given a an
     * array, it must have 1 to 2 items representing the minimum and optionlly
     * the maximim length.
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
        $lengthRange = null,
        ?int $flags = null,
        ?string $padString = null,
        ?int $padType = null,
        ?LiteralWorkbench $literalWorkbench = null
    ) {
        $this->literalWorkbench_ =
            $literalWorkbench ?? LiteralWorkbench::getMainInstance();

        $this->datatype_ = $this->literalWorkbench_->getSchema()->getGlobalType(
            $datatypeXName ?? static::SUPPORTED_DATATYPE_XNAMES[0]
        );

        if (!isset($datatypeXName)) {
            $this->supportedDatatype_ = $this->datatype_;
        } else {
            foreach (
                $this->datatype_
                    ->getSelfAndBaseTypes(AbstractSimpleType::class) as $type
            ) {
                if (
                    in_array(
                        (string)$type->getXName(),
                        static::SUPPORTED_DATATYPE_XNAMES
                    )
                ) {
                    $this->supportedDatatype_ = $type;
                    break;
                }
            }

            if (!isset($this->supportedDatatype_)) {
                /** @throw alcamo::exception::InvalidType if $datatype is
                 *  not supported by this serializer class. */
                throw (new InvalidType())->setMessageContext(
                    [
                        'type' => $datatypeXName,
                        'expectedOneOf' => static::SUPPORTED_DATATYPE_XNAMES
                    ]
                );
            }
        }

        if (isset($encoding)) {
            if (
                !isset(static::ENCODINGS[$encoding])
                    && !isset(static::ENCODINGS['*'])
            ) {
                /** @throw alcamo::exception::InvalidEnumerator if $encoding
                 *  is not supported. */
                throw (new InvalidEnumerator())->setMessageContext(
                    [
                        'value' => $encoding,
                        'expectedOneOf' => array_keys(static::ENCODINGS)
                    ]
                );
            }

            $this->encoding_ = $encoding;
        } else {
            $this->encoding_ = static::DEFAULT_ENCODING;
        }

        $this->padString_ = $padString
            ?? (static::ENCODINGS[$this->encoding_]
                ?? static::ENCODINGS['*'])[1];

        if ($this->padString_ == '' && $flags & self::TRUNCATE_SILENTLY) {
            /** @throw alcamo::exception::Unsupported if the output length
             *  MUST NOT be changed but TRUNCATE_SILENTLY is activated. */
            throw (new Unsupported())->setMessageContext(
                [ 'feature' => "truncation of {$this->encoding_}" ]
            );
        }

        if (isset($lengthRange)) {
            $this->lengthRange_ = $lengthRange instanceof NonNegativeRange
                ? $lengthRange
                : new NonNegativeRange(...$lengthRange);
        }

        $this->padType_ = $padType ?? static::PAD_TYPE;
        $this->flags_ = (int)$flags;
    }

    public function getDatatype(): SimpleTypeInterface
    {
        return $this->datatype_;
    }

    public function getEncoding(): string
    {
        return $this->encoding_;
    }

    public function getLengthRange(): ?NonNegativeRange
    {
        return $this->lengthRange_;
    }

    public function getFlags(): int
    {
        return $this->flags_;
    }

    public function getPadString(): string
    {
        return $this->padString_;
    }

    public function getPadType(): int
    {
        return $this->padType_;
    }

    public function getLiteralWorkbench(): LiteralWorkbench
    {
        return $this->literalWorkbench_;
    }

    public function getBitsPerCharacter(): int
    {
        return
            (static::ENCODINGS[$this->encoding_] ?? static::ENCODINGS['*'])[0];
    }

    /// Check whether $literal is supported for this serializer class
    protected function validateLiteralClass(LiteralInterface $literal): void
    {
        $literalDatatype = $this->literalWorkbench_->validateLiteral($literal);

        if (
            !$literalDatatype
                ->isEqualToOrDerivedFrom($this->datatype_->getXName())
        ) {
            /** @throw alcamo::exception::InvalidType if $literal type is not
             *  derived from the serializer datatype. */
            throw (new InvalidType())->setMessageContext(
                [
                    'type' => $literalDatatype->getXName(),
                    'extraMessage' => ' incompatible with serializer datatype '
                        . $this->datatype_->getXName()
                ]
            );
        }
    }

    /**
     * @brief Pad/truncate/throw if necessary
     *
     * @param $value Data possibly subject to length constraints
     */
    protected function adjustOutputLength(string $value): string
    {
        if (isset($this->lengthRange_)) {
            [ $minLength, $maxLength ] = $this->lengthRange_->getMinMax();

            if (isset($maxLength) && strlen($value) > $maxLength) {
                if ($this->flags_ & self::TRUNCATE_SILENTLY) {
                    /** If $value is too long and TRUNCATE_SILENTLY is set in
                     * the flags, truncate to the left or to the right,
                     * depending on $this->padType_. */
                    $value = $this->padType_ == STR_PAD_RIGHT
                        ? substr($value, 0, $maxLength)
                        : substr($value, -$maxLength);
                } else {
                    /** @throw alcamo::exception::LengthOutOfRange if $value
                     *  is too long and TRUNCATE_SILENTLY is not set. */
                    throw (new LengthOutOfRange())->setMessageContext(
                        [
                            'value' => $value,
                            'length' => strlen($value),
                            'upperBound' => $maxLength
                        ]
                    );
                }
            } elseif (isset($minLength)) {
                if ($this->padString_ == '' && strlen($value) < $minLength) {
                    /** @throw alcamo::exception::LengthOutOfRange if $value
                     *  is too short and no padding is possible. */
                    throw (new LengthOutOfRange())->setMessageContext(
                        [
                            'value' => $value,
                            'length' => strlen($value),
                            'lowerBound' => $minLength,
                            'upperBound' => $maxLength
                        ]
                    );
                }

                /** Pad to the minimum length if necessary. */
                $value = str_pad(
                    $value,
                    $minLength,
                    $this->padString_,
                    $this->padType_
                );
            }
        }

        /** Add padding as needed to get complete bytes in hte output. */
        if (strlen($value) & 1 && $this->getBitsPerCharacter() == 4) {
            $value = str_pad(
                $value,
                strlen($value) + 1,
                $this->padString_,
                $this->padType_
            );
        } elseif (strlen($value) & 7 && $this->getBitsPerCharacter() == 1) {
            $value = str_pad(
                $value,
                (strlen($value) + 7) >> 3 << 3,
                $this->padString_,
                $this->padType_
            );
        }

        return $value;
    }

    /// Check the input length
    protected function validateInputLength(string $input): void
    {
        if (
            isset($this->lengthRange_)
                && !($this->flags_ & self::SKIP_LENGTH_CHECK)
        ) {
            [ $minLength, $maxLength ] = $this->lengthRange_->getMinMax();

            if ($maxLength & 1 && $this->getBitsPerCharacter() == 4) {
                /** Add a padding nibble to maxLength if length is measured in
                 *  nibbles and maximum length is odd. */
                $maxLength++;
            } elseif ($maxLength & 7 && $this->getBitsPerCharacter() == 1) {
                /** Add padding bits to maxLength if length is measured in
                 *  bits and maximum length is not a multiple of 8. */
                $maxLength = ($maxLength + 7) >> 3 << 3;
            }

            /** @throw alcamo::exception::LengthOutOfRange is
             *  SKIP_LENGTH_CHECK is not set in the flags and the value is too
             *  short or too long. */
            LengthOutOfRange::throwIfOutside($input, $minLength, $maxLength);
        }
    }
}
