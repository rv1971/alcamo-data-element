<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\{AbstractSimpleType, SimpleTypeInterface};
use alcamo\exception\{
    InvalidEnumerator,
    InvalidType,
    LengthOutOfRange
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
     * Assigns to each supported encoding a triple, consisting of
     * - the number of bits per encoded character
     * - the padding string
     * - the padding type
     *
     * The encoding `*` represents all encodings not explicitely listed.
     */
    public const ENCODINGS = [];

    public static function newFromProps($props): SerializerInterface
    {
        $props = (object)$props;

        return new static(
            $props->datatypeXName ?? null,
            $props->encoding ?? null,
            $props->lengthRange ?? null,
            $props->flags ?? null,
            $props->deWorkbench ?? null
        );
    }

    /**
     * @brief Create from named properties an instance of a given class
     *
     * The class to use is given as the `class` property. Useful to create
     * from configuration parameters an instance of a class which is not yet
     * known at compile time.
     *
     * @param $props object or array of named properties corresponding to the
     * constructor parameters, plus a `class` item.
     */
    public static function createFromProps($props): SerializerInterface
    {
        $props = (object)$props;

        return ($props->class)::newFromProps($props);
    }

    protected $datatype_; ///< SimpleTypeInterface

    /**
     * @brief SimpleTypeInterface
     *
     * That datatype listed in SUPPORTED_DATATYPE_XNAMES from which $datytpe_
     * is derived.
     */
    protected $supportedDatatype_;

    protected $encodingParams_; ///< EncodingParams
    protected $lengthRange_;    ///< ?NonNegativeRange
    protected $flags_;          ///< int
    protected $deWorkbench_;    ///< DeWorkbench

    /**
     * @param $datatypeXName Datatype to use for deserialized literals
     * [default first item in SUPPORTED_DATATYPE_XNAMES]
     *
     * @parm $encoding A key from
     * alcamo::data_element::AbstractSerializer::ENCODINGS [default first key]
     *
     * @param $lengthRange NonNegativeRange|array Allowed length of serialized
     * data, in encoding-dependent units (bytes or nibbles or bits). If given
     * as an array, it must have 1 to 2 items representing the minimum and
     * optionally the maximim length.
     *
     * @param $flags Bitwise-OR-combination of the constants in
     * alcamo::data_element::SerializerInterface.
     *
     * @param $deWorkbench Workbench used in deserialize() and in
     * validateLiteralClass(). [default
     * alcamo::data_element::DeWorkbench::getMainInstance()]
     */
    public function __construct(
        ?string $datatypeXName = null,
        ?string $encoding = null,
        $lengthRange = null,
        ?int $flags = null,
        ?DeWorkbench $deWorkbench = null
    ) {
        $this->deWorkbench_ =
            $deWorkbench ?? DeWorkbench::getMainInstance();

        if (isset($datatypeXName)) {
            $this->datatype_ = $this->deWorkbench_->getSchema()
                ->getGlobalType($datatypeXName);

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
        } else {
            $this->datatype_ = $this->deWorkbench_->getSchema()
                ->getGlobalType(static::SUPPORTED_DATATYPE_XNAMES[0]);

            $this->supportedDatatype_ = $this->datatype_;
        }

        if (isset($encoding)) {
            $encodingParams =
                static::ENCODINGS[$encoding] ?? static::ENCODINGS['*'] ?? null;

            if (!isset($encodingParams)) {
                /** @throw alcamo::exception::InvalidEnumerator if $encoding
                 *  is not supported. */
                throw (new InvalidEnumerator())->setMessageContext(
                    [
                        'value' => $encoding,
                        'expectedOneOf' => array_keys(static::ENCODINGS)
                    ]
                );
            }
        } else {
            $encoding = array_key_first(static::ENCODINGS);
            $encodingParams = static::ENCODINGS[$encoding];
        }

        $this->encodingParams_ = new EncodingParams(
            $encoding,
            $encodingParams[0],
            $encodingParams[1] ?? null,
            $encodingParams[2] ?? null
        );

        if (isset($lengthRange)) {
            $this->lengthRange_ = $lengthRange instanceof NonNegativeRange
                ? $lengthRange
                : new NonNegativeRange(...$lengthRange);
        }

        $this->flags_ = (int)$flags;
    }

    public function getDatatype(): SimpleTypeInterface
    {
        return $this->datatype_;
    }

    public function getEncodingParams(): EncodingParams
    {
        return $this->encodingParams_;
    }

    public function getLengthRange(): ?NonNegativeRange
    {
        return $this->lengthRange_;
    }

    public function getFlags(): int
    {
        return $this->flags_;
    }

    public function getDeWorkbench(): DeWorkbench
    {
        return $this->deWorkbench_;
    }

    public function serializeToHex(
        LiteralInterface $literal,
        ?int $length = null
    ): string {
        return strtoupper(bin2hex($this->serialize($literal, $length)));
    }

    public function deserializeFromHex(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        return $this->deserialize(hex2bin($input), $datatype);
    }

    /// Check whether $literal is supported for this serializer class
    protected function validateLiteralClass(LiteralInterface $literal): void
    {
        $literalDatatype = $this->deWorkbench_->validateLiteral($literal);

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
    protected function adjustOutputLength(string $value, ?int $length): string
    {
        if (isset($length)) {
            if (isset($this->lengthRange_)) {
                [ $minLength, $maxLength ] = $this->lengthRange_->getMinMax();

                if (!($this->flags_ & self::SKIP_LENGTH_CHECK)) {
                    /** @throw alcamo::exception::LengthOutOfRange if
                     *  SKIP_LENGTH_CHECK is not set in the flags $length is
                     *  outside the length range of the serializer. */
                    LengthOutOfRange::throwIfOutside(
                        '<data-to-create>',
                        $minLength,
                        $maxLength,
                        $length
                    );
                }
            }

            $minLength = $maxLength = $length;
        } elseif (isset($this->lengthRange_)) {
            [ $minLength, $maxLength ] = $this->lengthRange_->getMinMax();
        }

        if (isset($minLength)) {
            if (isset($maxLength) && strlen($value) > $maxLength) {
                if ($this->flags_ & self::TRUNCATE_SILENTLY) {
                    /** If $value is too long and TRUNCATE_SILENTLY is set in
                     * the flags, truncate. */
                    $value =
                        $this->encodingParams_->truncate($value, $maxLength);
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
                /** Pad to the minimum length if necessary. */
                $value = $this->encodingParams_->pad($value, $minLength);
            }
        }

        return $value;
    }

    protected function hexToBin(string $hexData): string
    {
        return hex2bin($this->encodingParams_->align($hexData));
    }

    /// Check input length and remove padding if necessary
    protected function preprocessInput(string $input): string
    {
        /* Remove padding characters if necessary. */
        if (isset($this->lengthRange_)) {
            [ $minLength, $maxLength ] = $this->lengthRange_->getMinMax();

            if ($this->flags_ & self::SKIP_LENGTH_CHECK) {
                /** Even if SKIP_LENGTH_CHECK is set in the flags, remove a
                 *  padding nibble if necessary. */
                if (
                    $this->encodingParams_->getBitsPerCharacter() == 4
                        && strlen($input) == $maxLength + 1
                ) {
                    return $this->encodingParams_->unpad($input, $maxLength);
                } else {
                    return $input;
                }
            } else {
                $input = $this->encodingParams_->unpad($input, $maxLength);

                /** @throw alcamo::exception::LengthOutOfRange if
                 *  SKIP_LENGTH_CHECK is not set in the flags and the value is
                 *  too short or too long. */
                LengthOutOfRange::throwIfOutside(
                    $input,
                    $minLength,
                    $maxLength
                );

                return $input;
            }
        }

        return $input;
    }
}
