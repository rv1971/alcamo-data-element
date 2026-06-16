<?php

namespace alcamo\data_element;

use alcamo\dom\schema\{Schema, SchemaFactory, TypeMap};
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdf_literal\{LiteralFactory as RdfLiteralFactory, LiteralInterface};

/**
 * @brief Factory creating RDF literals from data types and values
 *
 * While alcamo::rdf_literal::LiteralFactory creates literals from a value, a
 * language and a datatype URI, this class creates them from a value and a
 * SimpleTypeInterface object.
 *
 * Unlike alcamo::rdf_literal::LiteralFactory, this class is aware of type
 * hierarchies. For instance, when given a value and a custom type derived
 * from `xsd:integer`, it is able to conclude that the needed literal class is
 * that for xsd:integer.
 *
 * Since there is no XML Schema datatype corresponding to LangStringLiteral,
 * this class does not support creating LangStringLiteral objects, and
 * therefore the create() method does not have a language parameter.
 *
 * @date Last reviewed 2026-05-04
 */
class LiteralFactory
{
    private $schema_;             ///< Schema
    private $typeToLiteralClass_; ///< TypeMap

    public function __construct(
        ?Schema $schema = null,
        ?RdfLiteralFactory $rdfLiteralFactory = null
    ) {
        $this->schema_ = $schema ?? (new SchemaFactory())->getMainSchema();

        if (!isset($rdfLiteralFactory)) {
            $rdfLiteralFactory = new RdfLiteralFactory();
        }

        $map = [];

        foreach (
            $rdfLiteralFactory::getDatatypeUriToClass() as $uri => $class
        ) {
            $map[(string)$this->schema_->createTypeFromUri($uri)->getXName()] =
                $class;
        }

        $this->typeToLiteralClass_ = new TypeMap($map);
    }

    public function getSchema(): Schema
    {
        return $this->schema_;
    }

    /**
     * @brief Create a literal
     *
     * @return RDF Literal object of the literal type of the closest ancestor
     * of $datatype for which a literal type is known, containing the URI of
     * $datatype itself.
     */
    public function create(
        $value,
        SimpleTypeInterface $datatype
    ): LiteralInterface {
        $class = $this->typeToLiteralClass_->lookup($datatype);

        /** If $value is an integer and $datatype is (derived from)
         *  `xsd:boolean`, accept any nonzero value as `true`. This differs
         *  from the bahaviour of alcamo::rdf_literal::BooleanLiteral which
         *  only accepts the lexical space of the boolean type (0,1, false and
         *  true). */
        if (
            is_int($value)
                && $datatype
                ->isEqualToOrDerivedFrom(Schema::XSD_NS . ' boolean')
        ) {
            $value = (bool)$value;
        }

        return new $class($value, $datatype->getUri());
    }
}
