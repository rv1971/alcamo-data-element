<?php

namespace alcamo\data_element;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\DataValidationFailed;
use alcamo\rdf_literal\LiteralInterface;
use alcamo\rdf_literal_workbench\{LiteralFactory, LiteralTypeMap};
use alcamo\uri\FileUriFactory;
use Composer\InstalledVersions;

/**
 * @brief Facade for literal factory and literal type map
 *
 * @date Last reviewed 2026-05-04
 */
class DeWorkbench
{
    /**
     * @brief Absolute paths to additional XSDs to load
     *
     * Paths that begin with `vendor` are automatically prefixed with the
     * parnet directory of _composer_autoload_path.
     */
    public const ADDTIONAL_XSD_PATHS = [
        'vendor' . DIRECTORY_SEPARATOR
            . 'alcamo' . DIRECTORY_SEPARATOR
            . 'rdf-literal' . DIRECTORY_SEPARATOR
            . 'xsd' . DIRECTORY_SEPARATOR . 'alcamo.rdf.xsd'
    ];

    private static $mainInstance_; ///< self

    public static function newFromFactories(
        LiteralFactory $literalFactory,
        LiteralTypeMap $literalTypeMap
    ): self {
        if ($literalFactory->getSchema() !== $literalTypeMap->getSchema()) {
            /** @throw alcamo::exception::DataValidationFailed on attempt to
             *  create a workbench from objects based on different schemas. */
            throw (new DataValidationFailed())->setMessageContext(
                [
                    'extraMessage' => 'Literal factory and literal type map '
                        . 'have different schemas'
                ]
            );
        }

        return new static($literalFactory, $literalTypeMap);
    }

    public static function newFromSchema(Schema $schema): self
    {
        return new static(
            new LiteralFactory($schema),
            new LiteralTypeMap($schema)
        );
    }

    public static function getMainInstance(): self
    {
        return self::$mainInstance_ ?? (
            self::$mainInstance_ =
                static::newFromSchema((new SchemaFactory())->getMainSchema())
        );
    }

    protected $schema_;         ///< Schema
    protected $literalFactory_; ///< LiteralFactory
    protected $literalTypeMap_; ///< LiteralTypeMap

    protected function __construct(
        LiteralFactory $literalFactory,
        LiteralTypeMap $literalTypeMap
    ) {
        $this->schema_ = $literalFactory->getSchema();
        $this->literalFactory_ = $literalFactory;
        $this->literalTypeMap_ = $literalTypeMap;

        $fileUriFactory = new FileUriFactory();

        $xsdUris = [];

        foreach (static::ADDTIONAL_XSD_PATHS as $xsdPath) {
            if (substr($xsdPath, 0, 7) == 'vendor' . DIRECTORY_SEPARATOR) {
                $xsdPath = substr($xsdPath, 7);

                $a = explode(DIRECTORY_SEPARATOR, $xsdPath, 3);

                $xsdPath = InstalledVersions::getInstallPath("{$a[0]}/{$a[1]}")
                    . DIRECTORY_SEPARATOR . $a[2];
            }

            $xsdUris[] = $fileUriFactory->create($xsdPath);
        }

        $this->schema_->addUris($xsdUris);
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

    public function createDataElementFromXName(
        $datatypeXName,
        $rdfaData = null
    ): DataElementInterface {
        return new DataElement(
            $this->schema_->getGlobalType($datatypeXName),
            $rdfaData
        );
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

    public function validateDeInstance(
        DeInstanceInterface $deInstance
    ): SimpleTypeInterface {
        $datatype = $this->validateLiteral($deInstance->getLiteral());

        $dataElementDatatypeXName =
            $deInstance->getDataElement()->getDatatype()->getXName();

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
