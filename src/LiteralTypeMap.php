<?php

namespace alcamo\data_element;

use alcamo\collection\ReadonlyCollection;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\dom\schema\SchemaFactory;
use alcamo\exception\DataValidationFailed;
use alcamo\rdfa\{LangStringLiteral, LiteralInterface, StringLiteral};

/**
 * @brief Map of literal classes to their datatypes
 *
 * @date Last reviewed 2026-02-27
 */
class LiteralTypeMap extends ReadonlyCollection
{
    private $schemaFactory_; ///< SchemaFactory

    public function __construct(
        ?SchemaFactory $schemaFactory = null
    ) {
        $this->schemaFactory_ = $schemaFactory ?? new SchemaFactory();
    }

    public function getSchemaFactory(): SchemaFactory
    {
        return $this->schemaFactory_;
    }

    /// Either returns true or throws an exception
    public function offsetExists($literalClass): bool
    {
        return (bool)$this->offsetGet($literalClass);
    }

    public function offsetGet($literalClass): SimpleTypeInterface
    {
        if (!isset($this->data_[$literalClass])) {
            $this->data_[$literalClass] =
                $this->createTypeFromUri($literalClass::DATATYPE_URI);
        }

        return $this->data_[$literalClass];
    }

    public function createTypeFromUri($datatypeUri): SimpleTypeInterface
    {
        return $this->schemaFactory_->createTypeFromUri(
            $datatypeUri == LangStringLiteral::DATATYPE_URI
                ? StringLiteral::DATATYPE_URI
                : $datatypeUri
        );
    }

    /**
     * @brief Check whether the literal's datatype is derived from the correct
     * type
     *
     * @return The literal's datatype.
     */
    public function validateLiteral(
        LiteralInterface $literal
    ): SimpleTypeInterface {
        $datatype = $this->createTypeFromUri($literal->getDatatypeUri());

        if (
            !$datatype->isEqualToOrDerivedFrom(
                $this[get_class($literal)]->getXName()
            )
        ) {
            throw (new DataValidationFailed())->setMessageContext(
                [
                    'extraMessage' => 'literal datatype '
                        . $datatype->getXName()
                        . ' not derived from type '
                        . $this[get_class($literal)]->getXName()
                ]
            );
        }

        return $datatype;
    }
}
