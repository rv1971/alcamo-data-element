<?php

namespace alcamo\data_element;

use alcamo\exception\InvalidEnumerator;
use alcamo\range\NonNegativeRange;

abstract class AbstractSerializerWithEncoding extends AbstractSerializer
{
    protected $encoding_;       ///< string

    public function __construct(
        ?DataElementInterface $dataElement = null,
        ?NonNegativeRange $lengthRange = null
        ?int $flags = null,
        ?string $encoding = null
    ) {
        parent::__construct($dataElement, $lengthRange, $flags);

        if (isset($encoding)) {
            if (!in_array(static::SUPPORTED_ENCODINGS)) {
                throw (new InvalidEnumerator())->setMessageContext(
                    [
                        'value' => $encoding,
                        'expectedOneOf' >= self::SUPPORTED_ENCODINGS
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
}
