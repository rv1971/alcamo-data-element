<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdfa\{HavingRdfaDataInterface, LiteralFactory, LiteralInterface};

class DataElement extends DataElementInterface
    implements HavingRdfaDataInterface
{
    private $datatype_; ///< SimpleTypeInterface
    private $rdfaData_; ///< ?RdfaData

    public function __construct(
        SimpleTypeInterface $datatype,
        $rdfaData = null
    ) {
        $this->datatype_ = $datatype;
        $this->rdfaData_ = (clone $datatype->getRdfaData())->replace(
            $rdfaData instanceof RdfaData
                ? $rdfaData
                : RdfaData::newFromIterable($rdfaData)
        );
    }

    public function getDatatype(): SimpleTypeInterface
    {
        return $this->datatype_;
    }

    public function getRdfaData(): ?RdfaData
    {
        return $this->rdfaData_;
    }

    public function createLiteral($value = null): LiteralInterface
    {
        $primitiveTypeXName = $this->datatype_->getPrimitiveType()->getXName();

        switch ($primitiveTypeXName->getLocalName()) {
            case 'decimal':
                return $this->datatype_->isIntegral()
                    ? new IntegerLiteral($value, $this->datatype_->getUri())
                    : new FloatLiteral($value, $this->datatype_->getUri());

            case 'langString':
                return new LangStringLiteral(
                    $value,
                    null,
                    $this->datatype_->getUri()
                );

            default:
                $class = LiteralFactory::DATATYPE_URI_TO_CLASS[
                    (string)$primitiveTypeXName
                ];

                return new $class($value, $this->datatype_->getUri());
        }
    }
}
