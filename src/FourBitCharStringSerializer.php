<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdf_literal\{FourBitCharStringLiteral, LiteralInterface};

/**
 * @brief (De)Serializer for four-bit string data
 *
 * @date Last reviewed 2026-04-21
 */
class FourBitCharStringSerializer extends StringSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES =
        [ FourBitCharStringLiteral::DEFAULT_DATATYPE_XNAME ];

    public const ENCODINGS = [
        'ASCII'    => [ 8, ' ', STR_PAD_RIGHT ],
        'FOUR-BIT' => [ 4, 'F', STR_PAD_RIGHT ]
    ];

    public function serialize(
        LiteralInterface $literal,
        ?int $length = null
    ): string {
        switch ($this->encodingParams_->getEncoding()) {
            case 'ASCII':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength($literal, $length);

            case 'FOUR-BIT':
                return
                    $this->hexToBin($this->serializeToHex($literal, $length));
        }
    }

    public function serializeToHex(
        LiteralInterface $literal,
        ?int $length = null
    ): string {
        switch ($this->encodingParams_->getEncoding()) {
            case 'ASCII':
                return strtoupper(bin2hex($this->serialize($literal, $length)));

            case 'FOUR-BIT':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength(
                    strtr($literal, ':;<=>?', 'ABCDEF'),
                    $length
                );
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        /** Remove trailing spaces from input unless four-bit-encoding. */
        switch ($this->encodingParams_->getEncoding()) {
            case 'FOUR-BIT':
                return $this->deserializeFromHex(bin2hex($input), $datatype);

            default:
                return $this->deWorkbench_->createLiteral(
                    rtrim($this->preprocessInput($input)),
                    $datatype ?? $this->datatype_
                );
        }
    }

    public function deserializeFromHex(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encodingParams_->getEncoding()) {
            case 'FOUR-BIT':
                $input = $this->preprocessInput($input);

                return $this->deWorkbench_->createLiteral(
                    strtr($input, 'ABCDEFabcdef', ':;<=>?:;<=>?'),
                    $datatype ?? $this->datatype_
                );

            default:
                return $this->deserialize($this->hexToBin($input), $datatype);
        }
    }
}
