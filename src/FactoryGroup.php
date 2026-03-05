<?php

namespace alcamo\data_element;

use alcamo\dom\schema\{Schema, SchemaFactory};
use alcamo\exception\DataValidationFailed;

/**
 * @brief Group of related factories that are typically needed together
 *
 * @date Last reviewed 2026-03-05
 */
class FactoryGroup
{
    private static $mainInstance_;  ///< self

    public static function newFromFactories(
        LiteralFactory $literalFactory,
        LiteralTypeMap $literalTypeMap
    ): self {
        if (
            $literalFactory->getSchemaFactory()
                !== $literalTypeMap->getSchemaFactory()
        ) {
            /** @throw alcamo::exception::DataValidationFailed on attempt to
             *  create a factory group from objects based on different schema
             *  factories.
             */
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
}
