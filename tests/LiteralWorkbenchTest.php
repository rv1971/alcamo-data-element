<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\exception\DataValidationFailed;
use PHPUnit\Framework\TestCase;

class LiteralWorkbenchTest extends TestCase
{
    public function testCreation(): void
    {
        $factoryGroup = LiteralWorkbench::getMainInstance();

        $this->assertInstanceOf(
            SchemaFactory::class,
            $factoryGroup->getSchemaFactory()
        );

        $this->assertInstanceOf(
            LiteralFactory::class,
            $factoryGroup->getLiteralFactory()
        );

        $this->assertInstanceOf(
            LiteralTypeMap::class,
            $factoryGroup->getLiteralTypeMap()
        );

        $this->assertSame(
            $factoryGroup->getSchemaFactory(),
            $factoryGroup->getLiteralFactory()->getSchemaFactory()
        );

        $this->assertSame(
            $factoryGroup->getSchemaFactory(),
            $factoryGroup->getLiteralTypeMap()->getSchemaFactory()
        );

        $factoryGroup2 = LiteralWorkbench::newFromFactories(
            $factoryGroup->getLiteralFactory(),
            $factoryGroup->getLiteralTypeMap()
        );

        $this->assertSame(
            $factoryGroup->getLiteralFactory(),
            $factoryGroup2->getLiteralFactory()
        );

        $this->assertSame(
            $factoryGroup->getLiteralTypeMap(),
            $factoryGroup2->getLiteralTypeMap()
        );
    }

    public function testException(): void
    {
        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage(
            'Validation failed; Literal factory and literal type map have '
                . 'different schema factories'
        );

        LiteralWorkbench::newFromFactories(
            new LiteralFactory(new SchemaFactory()),
            new LiteralTypeMap(new SchemaFactory()),
        );
    }
}
