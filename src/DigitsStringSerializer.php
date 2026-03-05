<?php

namespace alcamo\data_element;

use alcamo\rdfa\{DigitsStringLiteral, LiteralInterface};

/**
 * @brief (De)Serializer for digits string data
 *
 * @date Last reviewed 2026-02-24
 */
class DigitsStringSerializer extends AbstractSerializerWithEncoding
{
    public const SUPPORTED_DATATYPE_XNAMES =
        [ DigitsStringLiteral::DATATYPE_XNAME ];

    public const ENCODINGS_TO_BITS =
        [ 'ASCII' => 8, 'COMPRESSED-BCD' => 4, 'EBCDIC' => 8 ];

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength($literal);

            case 'COMPRESSED-BCD':
                $output = $this->adjustOutputLength($literal, 'F');

                if (strlen($output) & 1) {
                    $output .= 'F';
                }

                return hex2bin($output);

            case 'EBCDIC':
                return $this->adjustOutputLength(
                    strtr(
                        $literal,
                        '0123456789',
                        "\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9"
                    ),
                    "\x40"
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
            case 'EBCDIC':
                $value = (int)strtr(
                    $input,
                    "\x40\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9",
                    ' 0123456789'
                );
                break;

            default:
                $value = $input;
        }

        return $this->factoryGroup_->getLiteralFactory()->create(
            $this->datatype_,
            rtrim($value, ' f')
        );
    }
}
