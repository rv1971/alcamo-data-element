<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdf_literal\{
    ConstructedHexBinaryLiteral,
    ConstructedStringLiteral,
    LiteralInterface
};
use alcamo\rdf_literal_workbench\LiteralWorkbench;

/**
 * @brief Create data elements and validate data element instances
 *
 * @date Last reviewed 2026-05-04
 */
class DeWorkbench extends LiteralWorkbench
{
    public static function getMainInstance(): LiteralWorkbench
    {
        static $instance;

        return $instance ?? ($instance = new self());
    }

    public function createDataElementFromXName(
        string $datatypeXName,
        $rdfaData = null
    ): DataElementInterface {
        return new DataElement(
            $this->schema_->getGlobalType($datatypeXName),
            $rdfaData
        );
    }

    public function validateDeInstance(
        DeInstanceInterface $deInstance
    ): SimpleTypeInterface {
        $dataElementDatatypeXName =
            $deInstance->getDataElement()->getDatatype()->getXName();

        $datatype = $this->validateLiteral($deInstance->getLiteral());

        $datatype->throwUnlessEqualToOrDerivedFrom($dataElementDatatypeXName);

        if ($deInstance->hasChildren()) {
            foreach ($deInstance->getChildren() as $item) {
                $this->validateDeInstance($item);
            }
        }

        return $datatype;
    }

    public function createDeInstance(
        $value,
        DataElementInterface $dataElement
    ): DeInstanceInterface {
        if (!($dataElement instanceof ConstructedDataElement)) {
            return new DeInstance(
                $dataElement,
                $this->createLiteral($value, $dataElement->getDatatype())
            );
        }

        $childLiterals = [];

        $dataElement->rewind();

        foreach ($value as $key => $childValue) {
            $childLiterals[$key] = $this->createLiteral(
                $childValue,
                $dataElement->current()->getDatatype()
            );

            $dataElement->next();
        }

        while ($dataElement->current()) {
            $childLiterals[] = $this->createLiteral(
                null,
                $dataElement->current()->getDatatype()
            );

            $dataElement->next();
        }

        $class = $dataElement->getDatatype()
            ->isEqualToOrDerivedFrom(self::XSD_NS . ' hexBinary')
            ? ConstructedHexBinaryLiteral::class
            : ConstructedStringLiteral::class;

        return new DeInstance($dataElement, new $class($childLiterals));
    }
}
