<?php

namespace alcamo\data_element;

use alcamo\exception\InvalidEnumerator;
use alcamo\range\NonNegativeRange;

/**
 * @brief (De)Serializer with configurable encoding
 *
 * @date Last reviewed 2026-04-21
 */
abstract class AbstractSerializerWithEncoding extends AbstractSerializer
{
    /// Map of supported encodings to number of bits per encoded character
    public const ENCODING_TO_BITS = null;

    /// Default encoding
    public const DEFAULT_ENCODING = 'ASCII';

    protected $encoding_; ///< string

    public static function newFromProps(object $props): SerializerInterface
    {
        return new static(
            $props->datatypeXName ?? null,
            $props->lengthRange ?? null,
            $props->flags ?? null,
            $props->padString ?? null,
            $props->padType ?? null,
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
     * alcamo::data_element::AbstractSerializerWithEncoding::DEFAULT_ENCODING]
     *
     * @param $literalWorkbench Workbench used in deserialize() and in
     * validateLiteralClass(). [default
     * alcamo::data_element::LiteralWorkbench::getMainInstance()]
     */
    public function __construct(
        ?string $datatypeXName = null,
        ?NonNegativeRange $lengthRange = null,
        ?string $padString = null,
        ?int $padType = null,
        ?int $flags = null,
        ?string $encoding = null,
        ?LiteralWorkbench $literalWorkbench = null
    ) {
        parent::__construct(
            $datatypeXName,
            $lengthRange,
            $padString,
            $padType,
            $flags,
            $literalWorkbench
        );

        if (isset($encoding)) {
            if (!isset(static::ENCODING_TO_BITS[$encoding])) {
                /** @throw alcamo::exception::InvalidEnumerator if $encoding
                 *  is not supported. */
                throw (new InvalidEnumerator())->setMessageContext(
                    [
                        'value' => $encoding,
                        'expectedOneOf' => array_keys(static::ENCODING_TO_BITS)
                    ]
                );
            }

            $this->encoding_ = $encoding;
        } else {
            $this->encoding_ = static::DEFAULT_ENCODING;
        }
    }

    public function getEncoding(): string
    {
        return $this->encoding_;
    }

    public function getBitsPerCharacter(): int
    {
        return static::ENCODING_TO_BITS[$this->encoding_];
    }
}
