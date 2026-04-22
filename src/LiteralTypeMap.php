<?php

namespace alcamo\data_element;

use alcamo\collection\ReadonlyCollection;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\dom\schema\SchemaFactory;
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\{LangStringLiteral, LiteralInterface, StringLiteral};

/**
 * @brief Map of literal classes to their datatypes
 *
 * @date Last reviewed 2026-04-20
 */
class LiteralTypeMap
{
    private $schemaFactory_; ///< SchemaFactory
    private $map_ = [];      ///< map of string to SimpleTypeInterface

    public function __construct(
        ?SchemaFactory $schemaFactory = null
    ) {
        $this->schemaFactory_ = $schemaFactory ?? new SchemaFactory();
    }

    public function getSchemaFactory(): SchemaFactory
    {
        return $this->schemaFactory_;
    }

    public function getDefaultDatatype($literalClass): SimpleTypeInterface
    {
        if (!isset($this->map_[$literalClass])) {
            $this->map_[$literalClass] = $this->createTypeFromUri(
                $literalClass::getClassDefaultDatatypeUri()
            );
        }

        return $this->map_[$literalClass];
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
        return $this->schemaFactory_->createTypeFromUri(
            $datatypeUri == LangStringLiteral::getClassDefaultDatatypeUri()
                ? StringLiteral::getClassDefaultDatatypeUri()
                : $datatypeUri
        );
    }
}
