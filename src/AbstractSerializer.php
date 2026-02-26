<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\dom\schema\component\AbstractSimpleType;
use alcamo\exception\{InvalidType, LengthOutOfRange};
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\LiteralInterface;

/**
 * @brief (De)Serializer based on data element information
 *
 * @date Last reviewed 2026-02-24
 */
abstract class AbstractSerializer implements SerializerInterface
{
    /// Extended names of supported data eleemnt datatypes
    public const SUPPORTED_DATATYPE_XNAMES = [];

    /// URI of default datatype
    public const DEFAULT_DATATYPE_URI = '';

    /// Supported classes of literals to serialize
    public const SUPPORTED_LITERAL_CLASSES = [];

    private static $schemaFactory_;

    /// Schema factory used to create default data elements
    public static function getSchemaFactory(): SchemaFactory
    {
        return self::$schemaFactory_
            ?? (self::$schemaFactory_ = new SchemaFactory());
    }

    protected $dataElement_;       ///< DataElementInterface
    protected $supportedDatatype_; ///< SimpleTypeInterface
    protected $lengthRange_;       ///< ?NonNegativeRange
    protected $flags_;             ///< int
    protected $literalFactory_;    ///< LiteralFactory

    /**
     * @param $dataElement Defaults to a data element f type
     * DEFAULT_DATATYPE_URI
     *
     * @param $lengthRange Allowed length of serialized data, in
     * encoding-dependent units (bytes or nibbles).
     *
     * @param $flags Bitwise-OR-combination of the above constants
     */
    public function __construct(
        ?DataElementInterface $dataElement = null,
        ?NonNegativeRange $lengthRange = null,
        ?int $flags = null,
        ?LiteralFactory $literalFactory = null
    ) {
        if (!isset($dataElement)) {
            $this->dataElement_ = new DataElement(
                static::getSchemaFactory()
                    ->createTypeFromUri(static::DEFAULT_DATATYPE_URI)
            );

            $this->supportedDatatype_ = $this->dataElement_->getDatatype();
        } else {
            $this->dataElement_ = $dataElement;

            /* First check primitive types since in most cases
             * SUPPORTED_DATATYPE_XNAMES contains primitive types. */
            $this->supportedDatatype_ =
                $dataElement->getDatatype()->getPrimitiveType();

            if (
                !in_array(
                    $this->supportedDatatype_->getXName()->getPair(),
                    static::SUPPORTED_DATATYPE_XNAMES
                )
            ) {
                $this->supportedDatatype_ = null;

                for (
                    $type = $dataElement->getDatatype();
                    $type instanceof AbstractSimpleType;
                    $type = $type->getBaseType()
                ) {
                    if (
                        in_array(
                            $type->getXName()->getPair(),
                            static::SUPPORTED_DATATYPE_XNAMES
                        )
                    ) {
                        $this->supportedDatatype_ = $type;
                        break;
                    }
                }

                if (!isset($this->supportedDatatype_)) {
                    /** @throw alcamo::exception::InvalidType if $dataElement
                     *  does not have a type supported by this
                     *  serializer class. */
                    throw (new InvalidType())->setMessageContext(
                        [
                            'type' => $dataElement->getDatatype()->getXName(),
                            'expectedOneOf' => static::SUPPORTED_DATATYPE_XNAMES
                        ]
                    );
                }
            }
        }

        $this->lengthRange_ = $lengthRange;
        $this->flags_ = (int)$flags;
        $this->literalFactory_ = $literalFactory ?? new LiteralFactory();
    }

    public function getDataElement(): DataElementInterface
    {
        return $this->dataElement_;
    }

    public function getLengthRange(): ?NonNegativeRange
    {
        return $this->lengthRange_;
    }

    public function getFlags(): int
    {
        return $this->flags_;
    }

    public function getLiteralFactory(): LiteralFactory
    {
        return $this->literalFactory_;
    }

    abstract public function serialize(LiteralInterface $literal): string;

    /**
     * Create a literal object with a value obtained from $input and the
     * datatype URI of $this->dataElement_.
     */
    abstract public function deserialize(string $input): LiteralInterface;

    /// Check whether $literal is supported for this serializer class
    protected function validateLiteralClass(LiteralInterface $literal): void
    {
        /* First check whether the class of $literal is supported, then
         * whether it is derived from a supported class. */

        if (in_array(get_class($literal), static::SUPPORTED_LITERAL_CLASSES)) {
            return;
        }

        foreach (static::SUPPORTED_LITERAL_CLASSES as $class) {
            if ($literal instanceof $class) {
                return;
            }
        }

        /** @throw alcamo::exception::InvalidType if $literal is not supported
         *  by this serializer class. */
        throw (new InvalidType())->setMessageContext(
            [
                'type' => get_class($literal),
                'expectedOneOf' => static::SUPPORTED_LITERAL_CLASSES
            ]
        );
    }

    /**
     * @brief Pad/truncate/throw if necessary
     *
     * @param $value Data possibly subject to length constraints
     *
     * @param $padString Padding string, default space, as in str_pad.
     *
     * @param $padType STR_PAD_RIGHT or STR_PAD_LEFT, default STR_PAD_RIGHT,
     * as in str_pad.
     */
    protected function adjustOutputLength(
        string $value,
        ?string $padString = null,
        ?int $padType = null
    ): string {
        if (isset($this->lengthRange_)) {
            if (!isset($padType)) {
                $padType = STR_PAD_RIGHT;
            }

            [ $minLength, $maxLength ] = $this->lengthRange_->getMinMax();

            if (isset($maxLength) && strlen($value) > $maxLength) {
                if ($this->flags_ & self::TRUNCATE_SILENTLY) {
                    /** If $value is too long and TRUNCATE_SILENTLY is set in
                     * the flags, truncate to the left or to the right,
                     * depending on $padType. */
                    $value = $padType == STR_PAD_RIGHT
                        ? substr($value, 0, $maxLength)
                        : substr($value, -$maxLength);
                } else {
                    /** @throw If $value is too long and TRUNCATE_SILENTLY is
                     *  not set, throw alcamo::exception::LengthOutOfRange. */
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
                return str_pad($value, $minLength, $padString ?? ' ', $padType);
            }
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

            if (
                $this instanceof AbstractSerializerWithEncoding
                    && $maxLength & 1
                    && static::ENCODINGS_TO_BITS[$this->encoding_] == 4
            ) {
                $maxLength++;
            }

            /** @throw alcamo::exception::LengthOutOfRange is
             *  SKIP_LENGTH_CHECK is not set in the flags and the value is too
             *  short or too long. */
            LengthOutOfRange::throwIfOutside($input, $minLength, $maxLength);
        }
    }
}
