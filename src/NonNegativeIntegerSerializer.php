<?php

namespace alcamo\data_element;

use alcamo\binary_data\{Bcd, BinaryString};
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdf_literal\{LiteralInterface, PositiveGYearLiteral};

/**
 * @brief (De)Serializer for nonnegative integers
 *
 * @date Last reviewed 2026-04-21
 */
class NonNegativeIntegerSerializer extends IntegerSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' nonNegativeInteger',
        self::XSD_NS . ' boolean',
        self::XSD_NS . ' gDay',
        self::XSD_NS . ' gMonth',
        PositiveGYearLiteral::DEFAULT_DATATYPE_XNAME
    ];

    public const ENCODINGS = [
        'ASCII'      => [ 8, '0' ],
        'BCD'        => [ 4, '0' ],
        'BIG-ENDIAN' => [ 8, "\x00" ],
        'EBCDIC'     => [ 8, "\x40" ]
    ];

    public function serialize(LiteralInterface $literal): string
    {
        switch ($this->encoding_) {
            case 'BCD':
                return $this->hexToBin($this->serializeToHex($literal));

            default:
                return parent::serialize($literal);
        }
    }

    public function serializeToHex(LiteralInterface $literal): string
    {
        switch ($this->encoding_) {
            case 'BCD':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength(
                    Bcd::newFromInt($literal->toInt(), null, true)
                );

            default:
                return strtoupper(bin2hex($this->serialize($literal)));
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encoding_) {
            case 'BCD':
                return $this->deserializeFromHex(bin2hex($input));

            default:
                return parent::deserialize($input, $datatype);
        }
    }

    public function deserializeFromHex(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encoding_) {
            case 'BCD':
                $this->validateFourBitInputLength($input);

                return $this->deWorkbench_->createLiteral(
                    (int)$input,
                    $datatype ?? $this->datatype_
                );

            default:
                return $this->deserialize($this->hexToBin($input), $datatype);
        }
    }
}
