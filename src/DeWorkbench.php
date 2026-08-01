<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdf_literal\LiteralInterface;
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

        return $instance ??
            ($instance =
             self::newFromSchema((new SchemaFactory())->getMainSchema()));
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

        if ($deInstance instanceof ConstructedDeInstance) {
            foreach ($deInstance as $item) {
                $this->validateDeInstance($item);
            }
        }

        return $datatype;
    }
}
