<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdf_literal\{DigitStringLiteral, LiteralInterface};

/**
 * @brief (De)Serializer for digit string data
 *
 * @date Last reviewed 2026-04-21
 */
class DigitStringSerializer extends FourBitCharStringSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES =
        [ DigitStringLiteral::DEFAULT_DATATYPE_XNAME ];

    public const ENCODINGS = [
        'ASCII'          => [ 8, ' ' ],
        'COMPRESSED-BCD' => [ 4, 'F' ],
        'DUMP'           => [ 8, '' ],
        'EBCDIC'         => [ 8, "\x40" ]
    ];

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        if ($this->encoding_ == 'DUMP') {
            return $this->dump($literal);
        }

        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength($literal);

            case 'COMPRESSED-BCD':
                return hex2bin($this->adjustOutputLength($literal));

            case 'EBCDIC':
                return $this->adjustOutputLength(
                    strtr(
                        $literal,
                        '0123456789',
                        "\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9"
                    )
                );
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        /** Remove trailing padding characters from input. */

        switch ($this->encoding_) {
            case 'ASCII':
                $this->validateInputLength($input);

                $value = rtrim($input);
                break;

            case 'COMPRESSED-BCD':
                $input = bin2hex($input);

                $this->validateInputLength($input);

                $value = rtrim($input, 'f');
                break;

            case 'DUMP':
                return $this->dedump($input, $datatype);

            case 'EBCDIC':
                $value = rtrim(
                    strtr(
                        $input,
                        "\x40\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9",
                        ' 0123456789'
                    )
                );
                break;
        }

        return $this->literalWorkbench_
            ->createLiteral($value, $datatype ?? $this->datatype_);
    }
}
