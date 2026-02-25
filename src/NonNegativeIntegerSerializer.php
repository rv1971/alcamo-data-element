<?php

namespace alcamo\data_element;

use alcamo\binary_data\{Bcd, BinaryString};
use alcamo\dom\schema\component\AbstractSimpleType;
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{
    BooleanLiteral,
    GDayLiteral,
    GMonthLiteral
    NonNegativeIntegerLiteral,
    PositiveGYearLiteral,
    LiteralInterface
};

/**
 * @brief (De)Serializer for nonnegative integers
 *
 * @date Last reviewed 2026-02-24
 */
class NonNegativeIntegerSerializer extends AbstractSerializerWithEncoding
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        [ self::XSD_NS, 'boolean' ],
        [ self::XSD_NS, 'gDay' ],
        [ self::XSD_NS, 'gMonth' ],
        [ PositiveGYearLiteral::DATATYPE_XNAME ],
        [ self::XSD_NS, 'nonNegativeInteger' ]
    ];

    public const DEFAULT_DATATYPE_URI = NonNegativeIntegerLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [
        BooleanLiteral::class,
        GDayLiteral::class,
        GMonthLiteral::class,
        NonNegativeIntegerLiteral::class,
        PositiveGYearLiteral::class
    ];

    public const ENCODINGS_TO_BITS =
        [ 'ASCII' => 8, 'BCD' => 4, 'BIG-ENDIAN' => 8, 'EBCDIC' => 8 ];

    public const DEFAULT_ENCODING = 'ASCII';

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        $value = $literal->toInt();

        $minLength =
            isset($this->lengthRange_) ? $this->lengthRange_->getMin() : null;

        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength($value, '0', STR_PAD_LEFT);

            case 'BCD':
                /* adjustOutputLength() only checks the maximum length since
                 * the minimum length is already guaranteed. */
                return hex2bin(
                    $this->adjustOutputLength(
                        Bcd::newFromInt($value, $minLength)
                    )
                );

            case 'BIG-ENDIAN':
                /* adjustOutputLength() only checks the maximum length since
                 * the minimum length is already guaranteed. */
                return $this->adjustOutputLength(
                    BinaryString::newFromInt($value, $minLength)->getData()
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
                $value = (int)$input;
                break;

            case 'BCD':
                $value = Bcd::newFromString($input)->toInt();
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

        return $this->literalFactory_
            ->createLiteralForDataElement($this->dataElement_, $value);
    }
}
