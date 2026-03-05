<?php

namespace alcamo\data_element;

use alcamo\binary_data\{Bcd, BinaryString};
use alcamo\rdfa\{LiteralInterface, PositiveGYearLiteral};

/**
 * @brief (De)Serializer for nonnegative integers
 *
 * @date Last reviewed 2026-02-24
 */
class NonNegativeIntegerSerializer extends AbstractSerializerWithEncoding
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
        $this->validateLiteralClass($literal);

        $value = $literal->toInt();

        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength($value, '0', STR_PAD_LEFT);

            case 'BCD':
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

            case 'BIG-ENDIAN':
                $minLength = isset($this->lengthRange_)
                    ? $this->lengthRange_->getMin()
                    : null;

                /* adjustOutputLength() only checks the maximum length since
                 * the minimum length is already guaranteed. */
                return $this->adjustOutputLength(
                    BinaryString::newFromInt($value, $minLength)->getData(),
                    "\x00",
                    STR_PAD_LEFT
                );

            case 'EBCDIC':
                return $this->adjustOutputLength(
                    strtr(
                        $value,
                        '0123456789',
                        "\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9"
                    ),
                    "\xF0",
                    STR_PAD_LEFT
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
                $value = (int)$input;
                break;

            case 'BIG-ENDIAN':
                $value = (new BinaryString($input))->toInt();
                break;

            case 'EBCDIC':
                $value = (int)strtr(
                    $input,
                    "\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9",
                    '0123456789'
                );
                break;
        }

        return $this->factoryGroup_->getLiteralFactory()
            ->create($this->datatype_, $value);
    }
}
