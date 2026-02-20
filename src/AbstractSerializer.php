<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\AbstractSimpleType;
use alcamo\exception\{InvalidType, LengthOutOfRange};
use alcamo\rdfa\LiteralInterface;

abstract class AbstractSerializer implements SerializerInterface
{
    public const SUPPORTED_DATA_TYPE_XNAMES = [];

    public const SUPPORTED_LITERAL_CLASSES = [];

    protected $dataElement_;
    protected $extentRange_;
    protected $flags_;

    public function __construct(
        DataElementInterface $dataElement,
        ?NonNegativeRange $lengthRange = null
        ?int $flags = null
    ) {
        /* First check primitive types since in most cases
         * SUPPORTED_DATA_TYPE_XNAMES conatins primitive types. */
        if (
            !in_array(
                $dataElement->getType()->getPrimitiveType()->getXName()
                    ->getPair(),
                static::SUPPORTED_DATA_TYPE_XNAMES
            )
        ) {
            $supported = false;

            for (
                $type = $dataElement->getType();
                $type instanceof AbstractSimpleType;
                $type = $type->getBaseType()
            ) {
                if (
                    in_array(
                        $type->getXName()->getPair(),
                        static::SUPPORTED_DATA_TYPE_XNAMES
                    )
                ) {
                    $supported = true;
                    break;
                }
            }

            if (!$supported) {
                throw (new InvalidType())->setMessageContext(
                    'value' => $dataElement->getType()->getXName(),
                    'expectedOneOf' => static::SUPPORTED_DATA_TYPE_XNAMES
                );
            }
        }

        $this->dataElement_ = $dataElement;
        $this->extentRange_ = $extentRange;
        $this->flags_ = (int)$flags;
    }

    public function getDataElement(): DataElementInterface
    {
        return $this->dataElement_;
    }

    public function getExtentRange(): ?ExtentRange
    {
        return $this->extentRange_;
    }

    public function getFlags(): int
    {
        return $this->flags_;
    }

    public function serialize(?LiteralInterface $literal = null): string;

    public function deserialize(string $input): LiteralInterface;

    protected function validateLiteralClass(LiteralInterface $literal): void
    {
        foreach (static::SUPPORTED_LITERAL_CLASSES as $class) {
            if ($literal instanceof $class) {
                return;
            }
        }

        throw (new InvalidType())->setMessageContext(
            'value' => $literal,
            'expectedOneOf' => static::SUPPORTED_LITERAL_CLASSES
        );
    }

    protected function adjustOutputLength(
        string $value,
        string $padString,
        ?int $padType = null
    ): string {
        if (isset($this->extentRange_)) {
            if (!isset($padType)) {
                $padType = STR_PAD_RIGHT;
            }

            [ $minLength, $maxLength ] = $this->extentRange_->getMinMax();

            if (isset($maxLength) && strlen($value) > $maxLength) {
                if ($this->flags_ & self::TRUNCATE_SILENTLY) {
                    $value = $padType == STR_PAD_RIGHT
                        ? substr($value, 0, $maxLength)
                        : substr($value, -$maxLength);
                } else {
                    throw (new LengthOutOfRange())->setMessageContext(
                        [ 'value' => $value, 'upperBound' => $maxLength ]
                    );
                }
            } elseif (isset($minLength)) {
                return str_pad($value, $minLength, $padString, $padType);
            }

            return $value;
        }
    }

    protected function validateInputLength(string $input): void
    {
        if (
            isset($this->extentRange_)
                && !($this->flags_ & self::SKIP_LENGTH_CHECK)
        ) {
            [ $minLength, $maxLength ] = $this->extentRange_->getMinMax();

            LengthOutOfRange::throwIfOutside($input, $minLength, $maxLength);
        }
    }
}
