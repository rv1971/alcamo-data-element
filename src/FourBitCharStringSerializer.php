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
class FourBitCharStringSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES =
        [ FourBitCharStringLiteral::DEFAULT_DATATYPE_XNAME ];

    public const ENCODINGS = [
        'ASCII'    => [ 8, ' ' ],
        'FOUR-BIT' => [ 4, 'F' ]
    ];

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength($literal);

            case 'FOUR-BIT':
                return hex2bin(
                    $this->adjustOutputLength(
                        strtr($literal, ':;<=>?', 'ABCDEF')
                    )
                );
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encoding_) {
            case 'FOUR-BIT':
                $input = bin2hex($input);

                $this->validateInputLength($input);

                return $this->literalWorkbench_->createLiteral(
                    BinaryString::newFromHex($input)->toFourBitCharString(),
                    $datatype ?? $this->datatype_
                );

            default:
                $this->validateInputLength($input);

                /** Remove trailing spaces from input. */
                return $this->literalWorkbench_->createLiteral(
                    rtrim($input),
                    $datatype ?? $this->datatype_
                );
        }
    }
}
