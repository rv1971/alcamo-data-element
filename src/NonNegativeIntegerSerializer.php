<?php

namespace alcamo\data_element;

use alcamo\binary_data\{Bcd, BinaryString};
use alcamo\rdfa\{LiteralInterface, PositiveGYearLiteral};

/**
 * @brief (De)Serializer for nonnegative integers
 *
 * @date Last reviewed 2026-02-24
 */
class NonNegativeIntegerSerializer extends IntegerSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' nonNegativeInteger',
        self::XSD_NS . ' boolean',
        self::XSD_NS . ' gDay',
        self::XSD_NS . ' gMonth',
        PositiveGYearLiteral::DATATYPE_XNAME
    ];

    public const ENCODINGS_TO_BITS =
        [ 'ASCII' => 8, 'BCD' => 4, 'BIG-ENDIAN' => 8, 'EBCDIC' => 8 ];

    public function serialize(LiteralInterface $literal): string
    {
        if ($this->encoding_ == 'BCD') {
            $this->validateLiteralClass($literal);

            $value = $literal->toInt();

            $minLength = isset($this->lengthRange_)
                ? $this->lengthRange_->getMin()
                : null;

            /* adjustOutputLength() only checks the maximum length since
             * the minimum length is already guaranteed. */
            $output = $this->adjustOutputLength(
                Bcd::newFromInt($value, $minLength),
                '0',
                STR_PAD_LEFT
            );

            if (strlen($output) & 1) {
                $output = "0$output";
            }

            return hex2bin($output);
        }

        return parent::serialize($literal);
    }

    public function deserialize(string $input): LiteralInterface
    {
        if ($this->encoding_ == 'BCD') {
            $input = bin2hex($input);

            $this->validateInputLength($input);

            return $this->factoryGroup_->getLiteralFactory()
                ->create($this->datatype_, (int)$input);
        }

        return parent::deserialize($input);
    }
}
