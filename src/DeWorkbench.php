<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\LiteralInterface;
use alcamo\rdf_literal_workbench\LiteralWorkbench;

/**
 * @brief Create data elements and validate data element instances
 *
 * @date Last reviewed 2026-05-04
 */
class DeWorkbench extends LiteralWorkbench
{
    private static $mainInstance_; ///< self

    public static function getMainInstance(): LiteralWorkbench
    {
        return self::$mainInstance_ ?? (
            self::$mainInstance_ =
                static::newFromSchema((new SchemaFactory())->getMainSchema())
        );
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

        if (!$datatype->isEqualToOrDerivedFrom($dataElementDatatypeXName)) {
            /** @throw alcamo::exception::DataValidationFailed if the literal
             *  datatype is not derived from (or equal to) the data element's
             *  datatype. */
            throw (new DataValidationFailed())->setMessageContext(
                [
                    'extraMessage' => "literal datatype {$datatype->getXName()}"
                        . " not derived from data element datatype "
                        . $dataElementDatatypeXName
                ]
            );
        }

        return $datatype;
    }
}
