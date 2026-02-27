<?php

namespace alcamo\data_element;

use alcamo\dom\schema\SchemaFactory;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\rdfa\{
    AnyUriLiteral,
    Base64BinaryLiteral,
    BooleanLiteral,
    DateLiteral,
    DateTimeLiteral,
    DecimalLiteral,
    DigitsStringLiteral,
    DoubleLiteral,
    DurationLiteral,
    FloatLiteral,
    FourBitStringLiteral,
    GDayLiteral,
    GMonthDayLiteral,
    GMonthLiteral,
    GYearLiteral,
    GYearMonthLiteral,
    HexBinaryLiteral,
    IntegerLiteral,
    LangStringLiteral,
    LanguageLiteral,
    MediaTypeLiteral,
    NonNegativeIntegerLiteral,
    NotationLiteral,
    PositiveGYearLiteral,
    QNameLiteral,
    StringLiteral,
    TimeLiteral
};
use alcamo\exception\DataValidationFailed;
use PHPUnit\Framework\TestCase;

class LiteralTypeMapTest extends TestCase
{
    public const XSD_NS = SchemaFactory::XSD_NS;

    private static $literalTypeMap_;

    public static function setUpBeforeClass(): void
    {
        self::$literalTypeMap_ = new LiteralTypeMap();
    }

    /**
     * @dataProvider arrayAccessProvider
     */
    public function testArrayAccess($literalClass, $expectedLocalName): void
    {
        $this->assertSame(
            $expectedLocalName,
            self::$literalTypeMap_[$literalClass]->getXName()
                ->getLocalName()
        );

        $this->assertTrue(
            self::$literalTypeMap_
                ->createTypeFromUri($literalClass::DATATYPE_URI)
                ->isEqualToOrDerivedFrom(
                    self::$literalTypeMap_[$literalClass]->getXName()
                )
        );

        $this->assertTrue(
            isset(self::$literalTypeMap_[$literalClass])
        );
    }

    public function arrayAccessProvider(): array
    {
        return [
            [ AnyUriLiteral::class, 'anyURI' ],
            [ Base64BinaryLiteral::class, 'base64Binary' ],
            [ BooleanLiteral::class, 'boolean' ],
            [ DateLiteral::class, 'date' ],
            [ DateTimeLiteral::class, 'dateTime' ],
            [ DecimalLiteral::class, 'decimal' ],
            [ DigitsStringLiteral::class, 'DigitsString' ],
            [ DoubleLiteral::class, 'double' ],
            [ DurationLiteral::class, 'duration' ],
            [ FloatLiteral::class, 'float' ],
            [ FourBitStringLiteral::class, 'FourBitString' ],
            [ GDayLiteral::class, 'gDay' ],
            [ GMonthDayLiteral::class, 'gMonthDay' ],
            [ GMonthLiteral::class, 'gMonth' ],
            [ GYearLiteral::class, 'gYear' ],
            [ GYearMonthLiteral::class, 'gYearMonth' ],
            [ IntegerLiteral::class, 'integer' ],
            [ LangStringLiteral::class, 'string' ],
            [ LanguageLiteral::class, 'language' ],
            [ MediaTypeLiteral::class, 'string' ],
            [ NotationLiteral::class, 'NOTATION' ],
            [ PositiveGYearLiteral::class, 'PositiveGYear' ],
            [ QNameLiteral::class, 'QName' ],
            [ StringLiteral::class, 'string' ],
            [ TimeLiteral::class, 'time' ]
        ];
    }

    /**
     * @dataProvider validateLiteralProvider
     */
    public function testValidateLiteral($literal): void
    {
        $this->assertInstanceOf(
            SimpleTypeInterface::class,
            self::$literalTypeMap_->validateLiteral($literal)
        );
    }

    public function validateLiteralProvider(): array
    {
        return [
            [ new BooleanLiteral() ],
            [ new IntegerLiteral(42, self::XSD_NS . '#byte') ],
            [ new GYearLiteral(2027, PositiveGYearLiteral::DATATYPE_URI) ]
        ];
    }

    public function testOffsetGetException(): void
    {
        $this->expectException(\Error::class);

        $this->expectExceptionMessage("Class 'foo' not found");

        isset(self::$literalTypeMap_['foo']);
    }

    public function testValidateLiteralException(): void
    {
        $this->expectException(DataValidationFailed::class);

        $this->expectExceptionMessage(
        'Validation failed; literal datatype http://www.w3.org/2001/XMLSchema '
            . 'integer not derived from type http://www.w3.org/2001/XMLSchema '
            . 'nonNegativeInteger'
        );

        self::$literalTypeMap_->validateLiteral(
            new NonNegativeIntegerLiteral(42, IntegerLiteral::DATATYPE_URI)
        );
    }
}
