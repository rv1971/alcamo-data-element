<?php

namespace alcamo\data_element;

use alcamo\collection\ReadonlyCollectionTrait;
use alcamo\exception\InvalidType;
use alcamo\rdf_literal\{AbstractLiteral, LiteralInterface};

/**
 * @brief RDF constructed literal
 *
 * String literal made of a sequence of literals or `null` values.
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

    /**
     * @copydoc alcamo::rdf_literal::AbstractLiteral::PRIMITIVE_DATATYPE_URI
     *
     * The only possible primitive datatypes for a constructed literal are
     * `string`, `base64Binary` and `hexBinary` because they are the only
     * primitive datatypes whose value spaces have a concatenation operation.
     *
     * `string` is the simplest choice because the concatenation of the
     * lexical representations of any literals, with or without separators, is
     * always a valid lexical representation of a `string` literal. Thus, an
     * implementation of the __toString() method is trivial. For a primitive
     * datatype of 'hexBinary', it would be more complex, and for
     * `base64Binary`even more.
     */
    public const PRIMITIVE_DATATYPE_URI = self::XSD_NS . 'string';

    public const DEFAULT_DATATYPE_URI = self::ALCAMO_RDF_NS . 'Constructed';

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
                 *  is neither `null` nor a LiteralInterface object. */
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
