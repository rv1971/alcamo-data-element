<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{LangStringLiteral, LiteralInterface, StringLiteral};

/**
 * @brief (De)Serializer for string data
 *
 * @date Last reviewed 2026-02-24
 */
class StringSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        [ self::XSD_NS, 'anyURI' ],
        [ self::XSD_NS, 'NOTATION' ],
        [ self::XSD_NS, 'QName' ],
        [ self::XSD_NS, 'string' ]
    ];

    public const DEFAULT_DATATYPE_URI = StringLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [
        LangStringLiteral::class,
        StringLiteral::class
    ];

    /// String encoding used internally
    public const INTERNAL_ENCODING = 'UTF-8';

    /// Default encoding in serialized data
    public const DEFAULT_ENCODING = 'UTF-8';

    protected $encoding_; ///< string

    /**
     * @param $dataElement Defaults to a data element of type
     * DEFAULT_DATATYPE_URI
     *
     * @param $lengthRange Allowed length of serialized data, in
     * encoding-dependent units (bytes or nibbles).
     *
     * @param $flags Bitwise-OR-combination of the
     * alcamo::data_element::AbstractSerializer constants
     *
     * @parm $encoding Defaults to DEFAULT_ENCODING
     *
     * Unlike AbstractSerializerWithEncoding, in this class it is not checked
     * inf $encoding is supported.
     */
    public function __construct(
        ?DataElementInterface $dataElement = null,
        ?NonNegativeRange $lengthRange = null,
        ?int $flags = null,
        ?string $encoding = null,
        ?LiteralFactory $literalFactory = null
    ) {
        parent::__construct(
            $dataElement,
            $lengthRange,
            $flags,
            $literalFactory
        );

        $this->encoding_ = $encoding ?? static::DEFAULT_ENCODING;
    }

    public function getEncoding(): string
    {
        return $this->encoding_;
    }

    public function serialize(LiteralInterface $literal): string
    {
        $this->validateLiteralClass($literal);

        return $this->adjustOutputLength(
            static::INTERNAL_ENCODING == $this->encoding_
                ? $literal->getValue()
                : iconv(
                    static::INTERNAL_ENCODING,
                    $this->encoding_,
                    $literal->getValue()
                )
        );
    }

    public function deserialize(string $input): LiteralInterface
    {
        $this->validateInputLength($input);

        /** Remove trailing spaces from input. */
        return $this->literalFactory_->createLiteralForDataElement(
            $this->dataElement_,
            rtrim(
                static::INTERNAL_ENCODING == $this->encoding_
                    ? $input
                    : iconv(
                        $this->encoding_,
                        static::INTERNAL_ENCODING,
                        $input
                    )
            ),
        );
    }
}
