<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\{AbstractSimpleType, SimpleTypeInterface};
use alcamo\exception\{InvalidType, LengthOutOfRange};
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\LiteralInterface;

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

    protected $datatype_;     ///< SimpleTypeInterface

    /**
     * @brief SimpleTypeInterface
     *
     * That datatype listed in SUPPORTED_DATATYPE_XNAMES from which $datytpe_
     * is derived.
     */
    protected $supportedDatatype_;

    protected $lengthRange_;  ///< ?NonNegativeRange
    protected $flags_;        ///< int
    protected $factoryGroup_; ///< FactoryGroup

    /**
     * @param $datatypeXName Datatype for deserialized literals [default first
     * item in SUPPORTED_DATATYPE_XNAMES)
     *
     * @param $lengthRange Allowed length of serialized data, in
     * encoding-dependent units (bytes or nibbles).
     *
     * @param $flags Bitwise-OR-combination of the above constants.
     *
     * @param $factoryGroup Factory group used in deserialize() and in
     * validateLiteralClass(). [default FactoryGroup::getInstance()]
     */
    public function __construct(
        ?string $datatypeXName = null,
        ?NonNegativeRange $lengthRange = null,
        ?int $flags = null,
        ?FactoryGroup $factoryGroup = null
    ) {
        $this->factoryGroup_ = $factoryGroup ?? FactoryGroup::getMainInstance();

        $this->datatype_ = $this->factoryGroup_->getSchema()->getGlobalType(
            $datatypeXName ?? static::SUPPORTED_DATATYPE_XNAMES[0]
        );

        if (!isset($datatypeXName)) {
            $this->supportedDatatype_ = $this->datatype_;
        } else {
            for (
                $type = $this->datatype_;
                $type instanceof AbstractSimpleType;
                $type = $type->getBaseType()
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

        $this->lengthRange_ = $lengthRange;
        $this->flags_ = (int)$flags;
    }

    public function getDatatype(): SimpleTypeInterface
    {
        return $this->datatype_;
    }

    public function getLengthRange(): ?NonNegativeRange
    {
        return $this->lengthRange_;
    }

    public function getFlags(): int
    {
        return $this->flags_;
    }

    public function getFactoryGroup(): FactoryGroup
    {
        return $this->factoryGroup_;
    }

    public function getBitsPerCharacter(): int
    {
        return 8;
    }

    abstract public function serialize(LiteralInterface $literal): string;

    abstract public function deserialize(string $input): LiteralInterface;

    /// Check whether $literal is supported for this serializer class
    protected function validateLiteralClass(LiteralInterface $literal): void
    {
        $literalDatatype = $this->factoryGroup_->getLiteralTypeMap()
            ->validateLiteral($literal);

        if (
            !$literalDatatype->isEqualToOrDerivedFrom(
                $this->datatype_->getXName()
            )
        ) {
            /** @throw alcamo::exception::InvalidType if $literal type is not
             *  supported. */
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
     *
     * @param $padString Padding string. [default space, as in str_pad()]
     *
     * @param $padType STR_PAD_RIGHT or STR_PAD_LEFT. Truncation takes place
     * on the same side as padding. [default STR_PAD_RIGHT, as in str_pad()]
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

            /** Add a padding nibble to maxLength if length is measured in
             *  nibbles and maximum length is odd. */
            if ($maxLength & 1 && $this->getBitsPerCharacter() == 4) {
                $maxLength++;
            }

            /** @throw alcamo::exception::LengthOutOfRange is
             *  SKIP_LENGTH_CHECK is not set in the flags and the value is too
             *  short or too long. */
            LengthOutOfRange::throwIfOutside($input, $minLength, $maxLength);
        }
    }
}
