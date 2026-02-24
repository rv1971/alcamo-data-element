<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
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

    public const DEFAULT_DATATYPE_URI = DigitsStringLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [ DigitsStringLiteral::class ];

    public const ENCODINGS_TO_BITS = [ 'ASCII' => 8, 'COMPRESSED-BCD' => 4 ];

    public const DEFAULT_ENCODING = 'ASCII';

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength($literal, ' ');

            case 'COMPRESSED-BCD':
                $output = $this->adjustOutputLength($literal, 'F');

                if (strlen($output) & 1) {
                    $output .= 'F';
                }

                return hex2bin($output);
        }
    }

    public function deserialize(string $input): LiteralInterface
    {
        if ($this->encoding_ == 'COMPRESSED-BCD') {
            $input = bin2hex($input);
        }

        $this->validateInputLength($input);

        return new DigitsStringLiteral(
            rtrim($input, ' f'),
            $this->dataElement_->getDatatype()->getUri()
        );
    }
}
