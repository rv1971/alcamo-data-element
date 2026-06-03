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
        'ASCII'          => [ 8, ' ',    STR_PAD_RIGHT ],
        'COMPRESSED-BCD' => [ 4, 'F',    STR_PAD_RIGHT ],
        'EBCDIC'         => [ 8, "\x40", STR_PAD_RIGHT ]
    ];

    public function serialize(LiteralInterface $literal): string
    {
        switch ($this->encodingParams_->getEncoding()) {
            case 'ASCII':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength($literal);

            case 'COMPRESSED-BCD':
                return $this->hexToBin($this->serializeToHex($literal));

            case 'EBCDIC':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength(
                    strtr(
                        $literal,
                        '0123456789',
                        "\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9"
                    )
                );
        }
    }

    public function serializeToHex(LiteralInterface $literal): string
    {
        switch ($this->encodingParams_->getEncoding()) {
            case 'COMPRESSED-BCD':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength($literal);

            default:
                return strtoupper(bin2hex($this->serialize($literal)));
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        /** Remove trailing padding characters from input. */

        switch ($this->encodingParams_->getEncoding()) {
            case 'ASCII':
                $this->validateInputLength($input);

                $value = rtrim($input);
                break;

            case 'COMPRESSED-BCD':
                return $this->deserializeFromHex(bin2hex($input), $datatype);

            case 'EBCDIC':
                $this->validateInputLength($input);

                $value = rtrim(
                    strtr(
                        $input,
                        "\x40\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9",
                        ' 0123456789'
                    )
                );
                break;
        }

        return $this->deWorkbench_
            ->createLiteral($value, $datatype ?? $this->datatype_);
    }

    public function deserializeFromHex(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encodingParams_->getEncoding()) {
            case 'COMPRESSED-BCD':
                $this->validateFourBitInputLength($input);

                return $this->deWorkbench_->createLiteral(
                    rtrim($input, 'Ff'),
                    $datatype ?? $this->datatype_
                );

            default:
                return $this->deserialize($this->hexToBin($input), $datatype);
        }
    }
}
