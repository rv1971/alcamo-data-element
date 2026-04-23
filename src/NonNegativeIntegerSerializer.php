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

    public const ENCODING_TO_BITS = [
        'ASCII'      => 8,
        'BCD'        => 4,
        'BIG-ENDIAN' => 8,
        'EBCDIC'     => 8
    ];

    public const ENCODING_TO_PAD_STRING = [
        'ASCII'      => '0',
        'BCD'        => '0',
        'BIG-ENDIAN' => "\x00",
        'EBCDIC'     => "\x40"
    ];

    public function serialize(LiteralInterface $literal): string
    {
        if ($this->encoding_ == 'BCD') {
            $this->validateLiteralClass($literal);

            $value = $literal->toInt();

            $minLength = isset($this->lengthRange_)
                ? $this->lengthRange_->getMin()
                : null;

            return hex2bin(
                $this->adjustOutputLength(Bcd::newFromInt($value, $minLength))
            );
        }

        return parent::serialize($literal);
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if (static::ENCODING_TO_BITS[$this->encoding_] == 4) {
            $input = bin2hex($input);

            $this->validateInputLength($input);

            return $this->literalWorkbench_
                ->createLiteral((int)$input, $datatype ?? $this->datatype_);
        }

        return parent::deserialize($input, $datatype);
    }
}
