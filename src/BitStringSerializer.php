<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{BitStringLiteral, LiteralInterface};

/**
 * @brief (De)Serializer for bit string data
 *
 * @date Last reviewed 2026-04-21
 */
class BitStringSerializer extends DigitStringSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES =
        [ BitStringLiteral::DEFAULT_DATATYPE_XNAME ];

    public const ENCODING_TO_BITS = [
        'ASCII'  => 8,
        'BINARY' => 1,
        'EBCDIC' => 8,
        'X.690'  => 8
    ];

    public const ENCODING_TO_PAD_STRING = [
        'ASCII'  => ' ',
        'BINARY' => '0',
        'EBCDIC' => "\x40",
        'X.690'  => ''
    ];

    public function serialize(LiteralInterface $literal): string
    {
        switch ($this->encoding_) {
            case 'BINARY':
                $this->validateLiteralClass($literal);

                return BinaryString::newFromBitString(
                    $this->adjustOutputLength($literal)
                )->getData();

            case 'X.690':
                $this->validateLiteralClass($literal);

                $unusedBits = (8 - strlen($literal) % 8) % 8;

                return $this->adjustOutputLength(
                    pack('C', $unusedBits) . BinaryString::newFromBitString(
                        $literal . substr('0000000', 0, $unusedBits)
                    )->getData()
                );

            default:
                return parent::serialize($literal);
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encoding_) {
            case 'BINARY':
                $value = (new BinaryString($input))->toBitString();

                $this->validateInputLength($value);

                return $this->literalWorkbench_
                    ->createLiteral($value, $datatype ?? $this->datatype_);

            case 'X.690':
                $this->validateInputLength($input);

                $unusedBits = unpack('C', $input[0])[1];

                $value = (new BinaryString(substr($input, 1)))->toBitString();

                if ($unusedBits) {
                    $value = substr($value, 0, -$unusedBits);
                }

                return $this->literalWorkbench_
                    ->createLiteral($value, $datatype ?? $this->datatype_);

            default:
                return
                    parent::deserialize($input, $datatype ?? $this->datatype_);
        }
    }
}
