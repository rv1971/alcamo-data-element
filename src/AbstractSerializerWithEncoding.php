<?php

namespace alcamo\data_element;

use alcamo\exception\{InvalidEnumerator, LengthOutOfRange};
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
    public const DEFAULT_ENCODING = '';

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
            $this->encoding_ =  static::DEFAULT_ENCODING;
        }
    }

    public function getEncoding(): string
    {
        return $this->encoding_;
    }

    /// Check the input length
    protected function validateInputLength(string $input): void
    {
        if (
            isset($this->lengthRange_)
                && !($this->flags_ & self::SKIP_LENGTH_CHECK)
        ) {
            [ $minLength, $maxLength ] = $this->lengthRange_->getMinMax();

            if (
                $maxLength & 1
                    && static::ENCODINGS_TO_BITS[$this->encoding_] == 4
            ) {
                $maxLength++;
            }

            /** @throw alcamo::exception::LengthOutOfRange is
             *  SKIP_LENGTH_CHECK is not set in the flags and the value is too
             *  short or too long. */
            LengthOutOfRange::throwIfOutside($input, $minLength, $maxLength);
        }
    }
}
