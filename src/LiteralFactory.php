<?php

namespace alcamo\data_element;

use alcamo\dom\schema\{SchemaFactory, TypeMap};
use alcamo\rdfa\{LiteralFactory as RdfaLiteralFactory, LiteralInterface};

class LiteralFactory
{
    private $schemaFactory_;      ///< SchemaFactory
    private $rdfaLiteralFactory_; ///< RdfaLiteralFactory
    private $typeToLiteralClass_; ///< TypeMap

    public function __construct(
        ?SchemaFactory $schemaFactory = null,
        ?RdfaLiteralFactory $rdfaLiteralFactory = null
    ) {
        $this->schemaFactory_ = $schemaFactory ?? new SchemaFactory();

        $this->rdfaLiteralFactory_ =
            $rdfaLiteralFactory ?? new RdfaLiteralFactory();

        $map = [];

        foreach (
            $this->rdfaLiteralFactory_::DATATYPE_URI_TO_CLASS as $uri => $class
        ) {
            $typeXName =
                $this->schemaFactory_->createTypeFromUri($uri)->getXName();

            $map[(string)$typeXName] = $class;
        }

        $this->typeToLiteralClass_ = new TypeMap($map);
    }

    public function getSchemaFactory(): SchemaFactory
    {
        return $this->schemaFactory_;
    }

    public function getRdfaLiteralFactory(): RdfaLiteralFactory
    {
        return $this->rdfaLiteralFactory_;
    }

    public function getTypeToLiteralClass(): TypeMap
    {
        return $this->typeToLiteralClass_;
    }

    public function createLiteralForDataElement(
        DataElement $dataElement,
        $value = null
    ): LiteralInterface {
        $class =
            $this->typeToLiteralClass_->lookup($dataElement->getDatatype());

        return new $class($value, $dataElement->getDatatype()->getUri());
    }
}
