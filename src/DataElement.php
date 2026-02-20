<?php

namespace alcamo\data_element;

use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdfa\{
    LiteralFactory,
    LiteralInterface
};

class DataElement extends DataElementInterface
{
    private $datatype_; ///< SimpleTypeInterface

    public function __construct(SimpleTypeInterface $datatype)
    {
        $this->datatype_ = $datatype;
    }

    public function getDatatype(): SimpleTypeInterface
    {
        return $this->datatype_;
    }

    public function createLiteral($value = null): LiteralInterface
    {
        switch (
            $this->datatype_->getPrimitiveType()->getXName()->getLocalName()
        ) {
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
                    (string)$this->datatype_->getPrimitiveType()->getXName()
                ];

                return new $class($value, $this->datatype_->getUri());
        }
    }
}
