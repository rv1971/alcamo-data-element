<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdf_literal\LiteralInterface;

/**
 * @brief (De)Serializer for string data
 *
 * @date Last reviewed 2026-04-21
 */
class StringSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' string',
        self::XSD_NS . ' anyURI',
        self::XSD_NS . ' NOTATION',
        self::XSD_NS . ' QName'
    ];

    public const ENCODINGS = [
        'UTF-8' => [ 8, ' ' ], // default encoding
        '*'     => [ 8, ' ' ]
    ];

    /// String encoding used internally
    public const INTERNAL_ENCODING = 'UTF-8';

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        if (static::INTERNAL_ENCODING == $this->encoding_) {
            return $this->adjustOutputLength($literal->getValue());
        }

        $value = $literal->getValue();

        /* Pad to minimum length in internal encoding before character set
         * conversion takes place, because output encoding might have a
         * different representation of the padding character. */
        if (isset($this->lengthRange_)) {
            $value = str_pad(
                $value,
                $this->lengthRange_->getMin(),
                $this->padString_,
                $this->padType_
            );
        }

        return $this->adjustOutputLength(
            iconv(static::INTERNAL_ENCODING, $this->encoding_, $value)
        );
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $this->validateInputLength($input);

        /** Remove trailing spaces from input. */
        return $this->literalWorkbench_->createLiteral(
            rtrim(
                static::INTERNAL_ENCODING == $this->encoding_
                    ? $input
                    : iconv($this->encoding_, static::INTERNAL_ENCODING, $input)
            ),
            $datatype ?? $this->datatype_
        );
    }
}
