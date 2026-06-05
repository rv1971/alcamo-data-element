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
        'UTF-8' => [ 8, ' ', STR_PAD_RIGHT ], // default encoding
        '*'     => [ 8, ' ', STR_PAD_RIGHT ]
    ];

    /// String encoding used internally
    public const INTERNAL_ENCODING = 'UTF-8';

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        $value = $literal->getValue();

        if (
            $this->encodingParams_->getEncoding() == static::INTERNAL_ENCODING
        ) {
            return $this->adjustOutputLength($value);
        }

        /* Pad to minimum length in internal encoding before character set
         * conversion takes place, because output encoding might have a
         * different representation of the padding character. */
        if (isset($this->lengthRange_)) {
            $value = $this->encodingParams_
                ->pad($value, $this->lengthRange_->getMin());
        }

        return $this->adjustOutputLength(
            iconv(
                static::INTERNAL_ENCODING,
                $this->encodingParams_->getEncoding(),
                $value
            )
        );
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $input = $this->preprocessInput($input);

        $encoding = $this->encodingParams_->getEncoding();

        /** Remove trailing spaces from input after conversion to internal
         *  encoding. */
        return $this->deWorkbench_->createLiteral(
            rtrim(
                $encoding == static::INTERNAL_ENCODING
                    ? $input
                    : iconv($encoding, static::INTERNAL_ENCODING, $input)
            ),
            $datatype ?? $this->datatype_
        );
    }

    /** @copydoc alcamo::data_element::SerializerInterface::dump() */
    public function dump(LiteralInterface $literal): string
    {
        return (new Dumper())->dumpString($literal);
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        return $this->deWorkbench_->createLiteral(
            (new Dumper())->dedumpString($input),
            $datatype ?? $this->datatype_
        );
    }
}
