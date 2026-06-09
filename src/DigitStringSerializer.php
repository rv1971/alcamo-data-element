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

    public function serialize(
        LiteralInterface $literal,
        ?int $length = null
    ): string {
        switch ($this->encodingParams_->getEncoding()) {
            case 'ASCII':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength($literal, $length);

            case 'COMPRESSED-BCD':
                return $this
                    ->hexToBin($this->serializeToHex($literal, $length));

            case 'EBCDIC':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength(
                    strtr(
                        $literal,
                        EncodingParams::ASCII_CHARS,
                        EncodingParams::EBCDIC_CHARS
                    ),
                    $length
                );
        }
    }

    public function serializeToHex(
        LiteralInterface $literal,
        ?int $length = null
    ): string {
        switch ($this->encodingParams_->getEncoding()) {
            case 'COMPRESSED-BCD':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength($literal, $length);

            default:
                return strtoupper(bin2hex($this->serialize($literal, $length)));
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        /** Remove trailing padding characters from input. */

        switch ($this->encodingParams_->getEncoding()) {
            case 'ASCII':
                $value = rtrim($this->preprocessInput($input));
                break;

            case 'COMPRESSED-BCD':
                return $this->deserializeFromHex(bin2hex($input), $datatype);

            case 'EBCDIC':
                $value = rtrim(
                    strtr(
                        $this->preprocessInput($input),
                        EncodingParams::EBCDIC_CHARS,
                        EncodingParams::ASCII_CHARS
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
                $input = $this->preprocessInput($input);

                return $this->deWorkbench_->createLiteral(
                    rtrim($input, 'Ff'),
                    $datatype ?? $this->datatype_
                );

            default:
                return $this->deserialize($this->hexToBin($input), $datatype);
        }
    }
}
