<?php

namespace alcamo\data_element;

use alcamo\rdfa\{FourBitStringLiteral, LiteralInterface};

/**
 * @brief (De)Serializer for four-bit string data
 *
 * @date Last reviewed 2026-02-24
 */
class FourBitStringSerializer extends AbstractSerializerWithEncoding
{
    public const SUPPORTED_DATATYPE_XNAMES =
        [ FourBitStringLiteral::DATATYPE_XNAME ];

    public const DEFAULT_DATATYPE_URI = FourBitStringLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [ FourBitStringLiteral::class ];

    public const ENCODINGS_TO_BITS = [ 'ASCII' => 8, 'FOUR-BIT' => 4 ];

    public const DEFAULT_ENCODING = 'ASCII';

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength($literal, ' ');

            case 'FOUR-BIT':
                $output = $this->adjustOutputLength($literal->toHex(), 'F');

                if (strlen($output) & 1) {
                    $output .= 'F';
                }

                return hex2bin($output);
        }
    }

    public function deserialize(string $input): LiteralInterface
    {
        if ($this->encoding_ == 'FOUR-BIT') {
            $input = bin2hex($input);
        }

        $this->validateInputLength($input);

        return $this->encoding_ == 'FOUR-BIT'
            ? FourBitStringLiteral::newFromHex(
                $input,
                $this->dataElement_->getDatatype()->getUri()
            )
            : new FourBitStringLiteral(
                rtrim($input),
                $this->dataElement_->getDatatype()->getUri()
            );
    }
}
