<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
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

    public const ENCODINGS = [
        'ASCII'  => [ 8, ' ', STR_PAD_RIGHT ],
        'BINARY' => [ 1, '0', STR_PAD_RIGHT ],
        'EBCDIC' => [ 8, "\x40", STR_PAD_RIGHT ],
        'X.690'  => [ 8 ]
    ];

    public function serialize(
        LiteralInterface $literal,
        int $length = null
    ): string {
        switch ($this->encodingParams_->getEncoding()) {
            case 'BINARY':
                $this->validateLiteralClass($literal);

                return BinaryString::newFromBitString(
                    $this->getEncodingParams()->align(
                        $this->adjustOutputLength($literal, $length)
                    )
                )->getData();

            case 'X.690':
                $this->validateLiteralClass($literal);

                $unusedBits = (8 - strlen($literal) % 8) % 8;

                return $this->adjustOutputLength(
                    pack('C', $unusedBits) . BinaryString::newFromBitString(
                        $literal . substr('0000000', 0, $unusedBits)
                    )->getData(),
                    $length
                );

            default:
                return parent::serialize($literal, $length);
        }
    }

    public function serializeToHex(
        LiteralInterface $literal,
        int $length = null
    ): string {
        return strtoupper(bin2hex($this->serialize($literal, $length)));
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encodingParams_->getEncoding()) {
            case 'BINARY':
                return $this->deWorkbench_
                    ->createLiteral(
                        $this->preprocessInput(
                            (new BinaryString($input))->toBitString()
                        ),
                        $datatype ?? $this->datatype_
                    );

            case 'X.690':
                $input = $this->preprocessInput($input);

                $unusedBits = unpack('C', $input[0])[1];

                $value = (new BinaryString(substr($input, 1)))->toBitString();

                if ($unusedBits) {
                    $value = substr($value, 0, -$unusedBits);
                }

                return $this->deWorkbench_
                    ->createLiteral($value, $datatype ?? $this->datatype_);

            default:
                return
                    parent::deserialize($input, $datatype ?? $this->datatype_);
        }
    }

    public function deserializeFromHex(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        return $this->deserialize(hex2bin($input), $datatype);
    }
}
