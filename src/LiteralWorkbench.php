<?php

namespace alcamo\data_element;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\LiteralInterface;
use alcamo\uri\FileUriFactory;

/**
 * @brief Facade for literal factory and literal type map
 *
 * @date Last reviewed 2026-05-04
 */
class LiteralWorkbench
{
    /// Absolute path to the vendor directory, including trailing separator
    public const VENDOR_PATH = __DIR__ . DIRECTORY_SEPARATOR
        . '..' . DIRECTORY_SEPARATOR
        . 'vendor' . DIRECTORY_SEPARATOR;

    /// Absolute paths to additional XSDs to load
    public const ADDTIONAL_XSD_PATHS = [
        self::VENDOR_PATH . 'alcamo' . DIRECTORY_SEPARATOR
            . 'rdf-literal' . DIRECTORY_SEPARATOR
            . 'xsd' . DIRECTORY_SEPARATOR . 'alcamo.rdf.xsd'
    ];

    private static $mainInstance_; ///< self

    public static function newFromFactories(
        LiteralFactory $literalFactory,
        LiteralTypeMap $literalTypeMap
    ): self {
        if (
            $literalFactory->getSchemaFactory()
                !== $literalTypeMap->getSchemaFactory()
        ) {
            /** @throw alcamo::exception::DataValidationFailed on attempt to
             *  create a workbench from objects based on different schema
             *  factories. */
            throw (new DataValidationFailed())->setMessageContext(
                [
                    'extraMessage' => 'Literal factory and literal type map '
                        . 'have different schema factories'
                ]
            );
        }

        return new static($literalFactory, $literalTypeMap);
    }

    public static function newFromSchemaFactory(
        SchemaFactory $schemaFactory
    ): self {
        return new static(
            new LiteralFactory($schemaFactory),
            new LiteralTypeMap($schemaFactory)
        );
    }

    public static function getMainInstance(): self
    {
        return self::$mainInstance_ ?? (
            self::$mainInstance_
                = static::newFromSchemaFactory(new SchemaFactory())
        );
    }

    protected $schemaFactory_;  ///< SchemaFactory
    protected $schema_;         ///< Schema
    protected $literalFactory_; ///< LiteralFactory
    protected $literalTypeMap_; ///< LiteralTypeMap

    protected function __construct(
        LiteralFactory $literalFactory,
        LiteralTypeMap $literalTypeMap
    ) {
        $this->schemaFactory_ = $literalFactory->getSchemaFactory();
        $this->schema_ = $this->schemaFactory_->getMainSchema();
        $this->literalFactory_ = $literalFactory;
        $this->literalTypeMap_ = $literalTypeMap;

        $fileUriFactory = new FileUriFactory();

        $xsdUris = [];

        foreach (static::ADDTIONAL_XSD_PATHS as $xsdPath) {
            $xsdUris[] = $fileUriFactory->create($xsdPath);
        }

        $this->schema_->addUris($xsdUris);
    }

    public function getSchemaFactory(): SchemaFactory
    {
        return $this->schemaFactory_;
    }

    public function getSchema(): Schema
    {
        return $this->schema_;
    }

    public function getLiteralFactory(): LiteralFactory
    {
        return $this->literalFactory_;
    }

    public function getLiteralTypeMap(): LiteralTypeMap
    {
        return $this->literalTypeMap_;
    }

    public function createLiteral(
        $value,
        SimpleTypeInterface $datatype
    ): LiteralInterface {
        return $this->literalFactory_->create($value, $datatype);
    }

    /** @copydoc alcamo::data_element::LiteralTypeMap::validateLiteral */
    public function validateLiteral(
        LiteralInterface $literal
    ): SimpleTypeInterface {
        return $this->literalTypeMap_->validateLiteral($literal);
    }

    public function validateDataElementInstance(
        DataElementInstanceInterface $dataElementInstance
    ): SimpleTypeInterface {
        $datatype = $this->validateLiteral($dataElementInstance->getLiteral());

        $dataElementDatatypeXName =
            $dataElementInstance->getDataElement()->getDatatype()->getXName();

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
