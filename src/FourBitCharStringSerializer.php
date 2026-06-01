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
        'ASCII'    => [ 8, ' ' ],
        'FOUR-BIT' => [ 4, 'F' ]
    ];

    public function serialize(LiteralInterface $literal): string
    {
        switch ($this->encoding_) {
            case 'ASCII':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength($literal);

            case 'FOUR-BIT':
                return $this->hexToBin(
                    $this->serializeToHex($literal)
                );
        }
    }

    public function serializeToHex(LiteralInterface $literal): string
    {
        switch ($this->encoding_) {
            case 'ASCII':
                return strtoupper(bin2hex($this->serialize($literal)));

            case 'FOUR-BIT':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength(
                    strtr($literal, ':;<=>?', 'ABCDEF')
                );
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encoding_) {
            case 'FOUR-BIT':
                return $this->deserializeFromHex(bin2hex($input));

            default:
                $this->validateInputLength($input);

                /** Remove trailing spaces from input. */
                return $this->deWorkbench_->createLiteral(
                    rtrim($input),
                    $datatype ?? $this->datatype_
                );
        }
    }

    public function deserializeFromHex(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encoding_) {
            case 'FOUR-BIT':
                $this->validateFourBitInputLength($input);

                return $this->deWorkbench_->createLiteral(
                    strtr($input, 'ABCDEFabcdef', ':;<=>?:;<=>?'),
                    $datatype ?? $this->datatype_
                );

            default:
                return $this->deserialize($this->hexToBin($input), $datatype);
        }
    }
}
