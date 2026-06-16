<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{LangStringLiteral, LiteralInterface, StringLiteral};

/**
 * @brief Map of literal classes to their datatypes
 *
 * @date Last reviewed 2026-05-04
 */
class LiteralTypeMap
{
    private $schema_; ///< Schema

    /// map of string to SimpleTypeInterface
    private $literalClassToDefaultDatatype_ = [];

    public function __construct(?Schema $schema = null)
    {
        $this->schema_ = $schema ?? (new SchemaFactory())->getMainSchema();
    }

    public function getSchema(): Schema
    {
        return $this->schema_;
    }

    public function getDefaultDatatype($literalClass): SimpleTypeInterface
    {
        if (!isset($this->literalClassToDefaultDatatype_[$literalClass])) {
            $this->literalClassToDefaultDatatype_[$literalClass] =
                $this->createTypeFromUri(
                    $literalClass::getClassDefaultDatatypeUri()
                );
        }

        return $this->literalClassToDefaultDatatype_[$literalClass];
    }

    /**
     * @brief Check whether the literal's datatype is derived from the
     * literal's default datatype.
     *
     * @return The literal's datatype.
     */
    public function validateLiteral(
        LiteralInterface $literal
    ): SimpleTypeInterface {
        $datatype = $this->createTypeFromUri($literal->getDatatypeUri());

        $defaultDatatypeXName =
            $this->getDefaultDatatype(get_class($literal))->getXName();

        if (!$datatype->isEqualToOrDerivedFrom($defaultDatatypeXName)) {
            /** @throw alcamo::exception::DataValidationFailed if the literal
             *  datatype is not derived from (or equal to) the literal class's
             *  default datatype. */
            throw (new DataValidationFailed())->setMessageContext(
                [
                    'extraMessage' => "literal datatype {$datatype->getXName()}"
                        . " not derived from default datatype "
                        . $defaultDatatypeXName
                ]
            );
        }

        return $datatype;
    }

    protected function createTypeFromUri($datatypeUri): SimpleTypeInterface
    {
        return $this->schema_->createTypeFromUri(
            $datatypeUri == LangStringLiteral::getClassDefaultDatatypeUri()
                ? StringLiteral::getClassDefaultDatatypeUri()
                : $datatypeUri
        );
    }
}
