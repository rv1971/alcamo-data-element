<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdfa\LiteralInterface;

/**
 * @brief (De)Serializer for string data
 *
 * @date Last reviewed 2026-02-24
 */
class StringSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        self::XSD_NS . ' string',
        self::XSD_NS . ' anyURI',
        self::XSD_NS . ' NOTATION',
        self::XSD_NS . ' QName'
    ];

    /// String encoding used internally
    public const INTERNAL_ENCODING = 'UTF-8';

    /// Default encoding in serialized data
    public const DEFAULT_ENCODING = 'UTF-8';

    protected $encoding_; ///< string

    /**
     * @param $datatypeXName Datatype for deserialized literals [default first
     * item in SUPPORTED_DATATYPE_XNAMES)
     *
     * @param $lengthRange Allowed length of serialized data, in
     * encoding-dependent units (bytes or nibbles).
     *
     * @param $flags Bitwise-OR-combination of the
     * alcamo::data_element::AbstractSerializer constants
     *
     * @parm $encoding [default DEFAULT_ENCODING]
     *
     * @param $factoryGroup Factory group used in deserialize() and in
     * validateLiteralClass(). [default FactoryGroup::getInstance()]
     *
     * Unlike AbstractSerializerWithEncoding, in this class it is not checked
     * if $encoding is supported.
     */
    public function __construct(
        ?string $datatypeXName = null,
        ?NonNegativeRange $lengthRange = null,
        ?int $flags = null,
        ?string $encoding = null,
        ?FactoryGroup $factoryGroup = null
    ) {
        parent::__construct(
            $datatypeXName,
            $lengthRange,
            $flags,
            $factoryGroup
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
        return $this->factoryGroup_->getLiteralFactory()->create(
            $this->datatype_,
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
