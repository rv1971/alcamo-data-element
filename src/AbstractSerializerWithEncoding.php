<?php

namespace alcamo\data_element;

use alcamo\exception\InvalidEnumerator;
use alcamo\range\NonNegativeRange;

/**
 * @brief (De)Serializer with configurable encoding
 *
 * @date Last reviewed 2026-02-24
 */
abstract class AbstractSerializerWithEncoding extends AbstractSerializer
{
    /// Map of seupported encodings to number of bits per encoded character
    public const ENCODINGS_TO_BITS = [];

    /// Default encoding
    public const DEFAULT_ENCODING = 'ASCII';

    protected $encoding_; ///< string

    /**
     * @param $datatypeXName Datatype for deserialized literals [default first
     * item in SUPPORTED_DATATYPE_XNAMES)
     *
     * @param $lengthRange Allowed length of serialized data, in
     * encoding-dependent units (bytes or nibbles).
     *
     * @param $flags Bitwise-OR-combination of the
     * alcamo::data_element::AbstractSerializer constants.
     *
     * @parm $encoding [default DEFAULT_ENCODING]
     *
     * @param $factoryGroup Factory group used in deserialize() and in
     * validateLiteralClass(). [default FactoryGroup::getInstance()]
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

        if (isset($encoding)) {
            if (!isset(static::ENCODINGS_TO_BITS[$encoding])) {
                /** @throw alcamo::exception::InvalidEnumerator if $encoding
                 *  is not supported. */
                throw (new InvalidEnumerator())->setMessageContext(
                    [
                        'value' => $encoding,
                        'expectedOneOf' => array_keys(static::ENCODINGS_TO_BITS)
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
        return static::ENCODINGS_TO_BITS[$this->encoding_];
    }
}
