<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\rdfa\LiteralInterface;

/**
 * @brief (De)Serializer for integers
 *
 * @date Last reviewed 2026-02-24
 */
class IntegerSerializer extends AbstractSerializerWithEncoding
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' integer',
        self::XSD_NS . ' boolean',
        self::XSD_NS . ' gDay',
        self::XSD_NS . ' gMonth',
        self::XSD_NS . ' gYear'
    ];

    public const ENCODINGS_TO_BITS =
        [ 'ASCII' => 8, 'BIG-ENDIAN' => 8, 'EBCDIC' => 8 ];

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        $value = $literal->toInt();

        $minLength = isset($this->lengthRange_)
            ? $this->lengthRange_->getMin()
            : 0;

        /* adjustOutputLength() only checks the maximum length since
         * the minimum length is already guaranteed. */
        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength(
                    sprintf("%0{$minLength}d", $value),
                    ' ',
                    STR_PAD_LEFT
                );

            case 'BIG-ENDIAN':
                return $this->adjustOutputLength(
                    BinaryString::newFromInt($value, $minLength)->getData(),
                    "\x00",
                    STR_PAD_LEFT
                );

            case 'EBCDIC':
                return $this->adjustOutputLength(
                    strtr(
                        sprintf("%0{$minLength}d", $value),
                        '-0123456789',
                        "\x60\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9"
                    ),
                    "\x40",
                    STR_PAD_LEFT
                );
        }
    }

    public function deserialize(string $input): LiteralInterface
    {
        $this->validateInputLength($input);

        switch ($this->encoding_) {
            case 'ASCII':
                $value = (int)$input;
                break;

            case 'BIG-ENDIAN':
                $value = (new BinaryString($input))
                    ->toInt($this->datatype_->isSigned());
                break;

            case 'EBCDIC':
                $value = (int)strtr(
                    $input,
                    "\x60\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9",
                    '-0123456789'
                );
                break;
        }

        return $this->factoryGroup_->getLiteralFactory()
            ->create($this->datatype_, $value);
    }
}
