<?php

namespace alcamo\data_element;

use alcamo\collection\ReadonlyCollectionTrait;
use alcamo\exception\InvalidType;
use alcamo\rdfa\{AbstractLiteral, LiteralInterface};

/**
 * @brief RDFa constructed literal
 *
 * Literal made of a sequence of literals or `null` values.
 *
 * @date Last reviewed 2026-03-02
 */
class ConstructedLiteral extends AbstractLiteral implements
    \Countable,
    \ArrayAccess,
    \Iterator
{
    use ReadonlyCollectionTrait;

    public const ALCAMO_DE_NS = 'tag:rv1971@web.de,2021:alcamo:ns:de#';

    public const DATATYPE_URI = self::ALCAMO_DE_NS . 'Constructed';

    public const PRIMITIVE_DATATYPE_URI = self::DATATYPE_URI;

    public const SEPARATOR = '|';

    public function __construct(iterable $value = null, $datatypeUri = null)
    {
        /* ReadonlyCollectionTrait accesses $data_. */
        foreach ($value as $key => $literal) {
            if (isset($literal) && !($literal instanceof LiteralInterface)) {
                /** @throw alcamo::exception::InvalidType if an item in $value
                 *  is neither `null`nor a LiteralInterface object. */
                throw (new InvalidType())->setMessageContext(
                    [
                        'value' => $literal,
                        'expectedOneOf' => LiteralInterface::class
                    ]
                );
            }

            $this->data_[$key] = $literal;
        }

        parent::__construct(null, $datatypeUri);

        /* AbstractLiteral accesses $value_. */
        $this->value_ =& $this->data_;
    }

    public function __toString(): string
    {
        return implode(static::SEPARATOR, $this->value_);
    }

    public function getDigest(): string
    {
        foreach ($this->value_ as $item) {
            if (isset($result)) {
                $result .= static::SEPARATOR
                    . (isset($item) ? $item->getDigest() : '');
            } else {
                $result = isset($item) ? $item->getDigest() : '';
            }
        }

        return $result ?? '';
    }
}
