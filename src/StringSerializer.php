<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
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

    /// String encoding used internally
    public const INTERNAL_ENCODING = 'UTF-8';

    /// Default encoding in serialized data
    public const DEFAULT_ENCODING = 'UTF-8';

    protected $encoding_; ///< string

    public static function newFromProps(object $props): SerializerInterface
    {
        return new static(
            $props->datatypeXName ?? null,
            $props->lengthRange ?? null,
            $props->flags ?? null,
            $props->encoding ?? null,
            $props->literalWorkbench ?? null
        );
    }

    /**
     * @param $datatypeXName Datatype for deserialized literals [default first
     * item in SUPPORTED_DATATYPE_XNAMES)
     *
     * @param $lengthRange Allowed length of serialized data, in
     * encoding-dependent units (bytes or nibbles).
     *
     * @param $flags Bitwise-OR-combination of the constants in
     * alcamo::data_element::SerializerInterface.
     *
     * @parm $encoding [default
     * alcamo::data_element::StringSerializer::DEFAULT_ENCODING]
     *
     * @param $literalWorkbench Workbench used in deserialize() and in
     * validateLiteralClass(). [default
     * alcamo::data_element::LiteralWorkbench::getMainInstance()]
     *
     * Unlike alcamo::data_element::AbstractSerializerWithEncoding, in
     * this class it is not checked if $encoding is supported.
     */
    public function __construct(
        ?string $datatypeXName = null,
        ?NonNegativeRange $lengthRange = null,
        ?int $flags = null,
        ?string $encoding = null,
        ?FactoryGroup $literalWorkbench = null
    ) {
        parent::__construct(
            $datatypeXName,
            $lengthRange,
            ' ',
            STR_PAD_RIGHT,
            $flags,
            $literalWorkbench
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

    public function deserialize(string $input): LiteralInterface
    {
        $this->validateInputLength($input);

        /** Remove trailing spaces from input. */
        return $this->literalWorkbench_->createLiteral(
            rtrim(
                static::INTERNAL_ENCODING == $this->encoding_
                    ? $input
                    : iconv($this->encoding_, static::INTERNAL_ENCODING, $input)
            ),
            $this->datatype_
        );
    }
}
