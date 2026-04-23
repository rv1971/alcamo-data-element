<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{BitsStringLiteral, LiteralInterface};

/**
 * @brief (De)Serializer for bits string data
 *
 * @date Last reviewed 2026-04-21
 */
class BitsStringSerializer extends DigitsStringSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES =
        [ BitsStringLiteral::DEFAULT_DATATYPE_XNAME ];

    public const ENCODING_TO_BITS = [
            'ASCII'  => 8,
            'BINARY' => 1,
            'EBCDIC' => 8
    ];

    public const ENCODING_TO_PAD_STRING = [
        'ASCII'  => ' ',
        'BINARY' => '0',
        'EBCDIC' => "\x40"
    ];

    public function serialize(LiteralInterface $literal): string
    {
        switch ($this->encoding_) {
            case 'BINARY':
                $this->validateLiteralClass($literal);

                return BinaryString::newFromBitsString(
                    $this->adjustOutputLength($literal)
                )->getData();

            default:
                return parent::serialize($literal);
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        /** Remove trailing padding characters from input. */

        switch ($this->encoding_) {
            case 'BINARY':
                $value = (new BinaryString($input))->toBitsString();

                $this->validateInputLength($value);

                return $this->literalWorkbench_
                    ->createLiteral($value, $datatype ?? $this->datatype_);

            default:
                return
                    parent::deserialize($input, $datatype ?? $this->datatype_);
        }
    }
}
