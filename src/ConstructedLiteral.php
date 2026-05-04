<?php

namespace alcamo\data_element;

use alcamo\collection\ReadonlyCollectionTrait;
use alcamo\exception\InvalidType;
use alcamo\rdf_literal\{AbstractLiteral, LiteralInterface};

/**
 * @brief RDF constructed literal
 *
 * String literal made of a sequence of literals, which may be of any type.
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
     * @param $value Iterable of alcamo::rdf_literal::LiteralInterface objects.
     *
     * @param $datatypeUri Datatype IRI.
     *
     * Keys if $value are preserved, i.e. the created object supports
     * iteration and array access with the same keys as used in $value.
     */
    public function __construct(iterable $value = null, $datatypeUri = null)
    {
        foreach ($value as $key => $literal) {
            if (!($literal instanceof LiteralInterface)) {
                /** @throw alcamo::exception::InvalidType if an item in $value
                 *  is not a LiteralInterface object. */
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


    /**
     * @copybrief alcamo::rdf_literal::LiteralInterface::__toString
     *
     * @return Concatenation of the return values of the __toString() methods
     * of each item, separated by
     * alcamo::rdf_literal::ConstructedLiteral::SEPARATOR.
     */
    public function __toString(): string
    {
        return implode(static::SEPARATOR, $this->value_);
    }

    /**
     * @copybrief alcamo::rdf_literal::LiteralInterface::getDigest()
     *
     * @return Concatenation of the return values of the getDigest() methods
     * of each item, separated by
     * alcamo::rdf_literal::ConstructedLiteral::SEPARATOR.
     */
    public function getDigest(): string
    {
        $result = [];

        foreach ($this->value_ as $item) {
            $result[] = $item->getDigest();
        }

        return implode(static::SEPARATOR, $result);
    }

    /**
     * @copybrief alcamo::rdf_literal::LiteralInterface::getDigest()
     *
     * The values of two constructed literals are considered equal if they
     * have the same number of items and corresponding items are considered
     * equal.
     */
    public function equals(LiteralInterface $literal): bool
    {
        if (
            $literal::PRIMITIVE_DATATYPE_URI != $this::PRIMITIVE_DATATYPE_URI
                || count($literal) != count($this)
        ) {
            return false;
        }

        $this->rewind();

        foreach ($literal as $item) {
            if (!$item->equals($this->current())) {
                return false;
            }

            $this->next();
        }

        return true;
    }
}
