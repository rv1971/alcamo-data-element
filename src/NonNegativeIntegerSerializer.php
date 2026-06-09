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
        'ASCII'      => [ 8, '0',    STR_PAD_LEFT ],
        'BCD'        => [ 4, '0',    STR_PAD_LEFT ],
        'BIG-ENDIAN' => [ 8, "\x00", STR_PAD_LEFT ],
        'EBCDIC'     => [ 8, "\x40", STR_PAD_LEFT ]
    ];

    public function serialize(
        LiteralInterface $literal,
        ?int $length = null
    ): string {
        switch ($this->encodingParams_->getEncoding()) {
            case 'BCD':
                return
                    $this->hexToBin($this->serializeToHex($literal, $length));

            default:
                return parent::serialize($literal, $length);
        }
    }

    public function serializeToHex(
        LiteralInterface $literal,
        ?int $length = null
    ): string {
        switch ($this->encodingParams_->getEncoding()) {
            case 'BCD':
                $this->validateLiteralClass($literal);

                return $this->adjustOutputLength(
                    Bcd::newFromInt($literal->toInt(), null, true),
                    $length
                );

            default:
                return strtoupper(bin2hex($this->serialize($literal, $length)));
        }
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encodingParams_->getEncoding()) {
            case 'BCD':
                return $this->deserializeFromHex(bin2hex($input), $datatype);

            default:
                return parent::deserialize($input, $datatype);
        }
    }

    public function deserializeFromHex(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        switch ($this->encodingParams_->getEncoding()) {
            case 'BCD':
                $input = $this->preprocessInput($input);

                return $this->deWorkbench_->createLiteral(
                    (int)$input,
                    $datatype ?? $this->datatype_
                );

            default:
                return $this->deserialize($this->hexToBin($input), $datatype);
        }
    }
}
