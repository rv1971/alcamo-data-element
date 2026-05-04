<?php

namespace alcamo\data_element;

use alcamo\collection\ReadonlyCollectionTrait;
use alcamo\exception\InvalidType;
use alcamo\rdf_literal\{AbstractLiteral, LiteralInterface};

/**
 * @brief RDF constructed literal
 *
 * Literal made of a sequence of literals or `null` values.
 *
 * There is no way to create an XML Schema datatype that semantically
 * expresses a sequence of items of potentially different types, potentially
 * separated by something that is not whitespace (or no separator at
 * all). Therefore, this package adopts the same solution as in LangString
 * literals: an artificial datatype URI is used that does not resolve to an
 * XML Schema type.
 *
 * @date Last reviewed 2026-04-21
 */
class ConstructedLiteral extends AbstractLiteral implements
    \Countable,
    \ArrayAccess,
    \Iterator
{
    use ReadonlyCollectionTrait;

    public const PRIMITIVE_DATATYPE_URI = self::XSD_NS . 'string';

    public const ALCAMO_DE_NS = 'tag:rv1971@web.de,2021:alcamo:ns:de#';

    public const DEFAULT_DATATYPE_URI = self::ALCAMO_DE_NS . 'Constructed';

    /// Separator used in __toString() and getDigest()
    public const SEPARATOR = '|';

    /**
     * @param $value Iterable of `null` values and
     * alcamo::rdf_literal::LiteralInterface objects.
     *
     * @param $datatypeUri Datatype IRI.
     */
    public function __construct(iterable $value = null, $datatypeUri = null)
    {
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

            /* ReadonlyCollectionTrait accesses $data_. */
            $this->data_[$key] = $literal;
        }

        parent::__construct(null, $datatypeUri ?? static::DEFAULT_DATATYPE_URI);

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

    public function equals(LiteralInterface $literal): bool
    {
        if (count($literal) != count($this)) {
            return false;
        }

        $this->rewind();

        foreach ($literal as $item) {
            if (
                isset($item) != ($this->current() !== null)
                    || (isset($item) && !$item->equals($this->current()))
            ) {
                return false;
            }

            $this->next();
        }

        return true;
    }
}
