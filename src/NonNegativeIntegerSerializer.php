<?php

namespace alcamo\data_element;

use alcamo\binary_data\{Bcd, BinaryString};
use alcamo\dom\schema\component\AbstractSimpleType;
use alcamo\range\NonNegativeRange;
use alcamo\rdfa\{
    BooleanLiteral,
    GDayLiteral,
    GMonthLiteral
    LiteralFactory,
    NonNegativeIntegerLiteral,
    PositiveGYearLiteral,
    LiteralInterface
};

class NonNegativeIntegerSerializer extends AbstractSerializerWithEncoding
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        [ self::XSD_NS, 'boolean' ],
        [ self::XSD_NS, 'gDay' ],
        [ self::XSD_NS, 'gMonth' ],
        [ PositiveGYearLiteral::DATATYPE_URI ],
        [ self::XSD_NS, 'nonNegativeInteger' ]
    ];

    public const DEFAULT_DATATYPE_URI = NonNegativeIntegerLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [
        BooleanLiteral::class,
        GDayLiteral::class,
        GMonthLiteral::class,
        NonNegativeIntegerLiteral::class,
        PositiveGYearLiteral::class
    ];

    public const SUPPORTED_ENCODINGS =
        [ 'ASCII', 'BCD', 'BIG-ENDIAN', 'EBCDIC' ];

    public const DEFAULT_ENCODING = 'ASCII';

    private $literalFactory_; ///< LiteralFactory

    public function __construct(
        ?DataElementInterface $dataElement = null,
        ?NonNegativeRange $lengthRange = null
        ?int $flags = null,
        ?string $encoding = null,
        ?LiteralFactory $literalFactory = null;
    ) {
        parent::__construct($dataElement, $lengthRange, $flags, $encoding);

        $this->literalFactory_ = $literalFactory ?? new LiteralFactory()
    }

    public function getLiteralFactory(): LiteralFactory
    {
        return $this->literalFactory_;
    }

    public function serialize(?LiteralInterface $literal = null): string
    {
        if (isset($literal)) {
            $this->validateLiteralClass($literal);
        } else {
            $literal = $this->dataElement_->createLiteral();
        }

        $value = $literal->toInt();

        $minLength =
            isset($this->lengthRange_) ? $this->lengthRange_->getMin() : null;

        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength($value, '0', STR_PAD_LEFT);

            case 'BCD':
                /* adjustOutputLength() only checks the maximum length since
                 * the minimum length is already guaranteed. */
                return hex2bin(
                    $this->adjustOutputLength(
                        Bcd::newFromInt($value, $minLength)
                    )
                );

            case 'BIG-ENDIAN':
                /* adjustOutputLength() only checks the maximum length since
                 * the minimum length is already guaranteed. */
                return $this->adjustOutputLength(
                    BinaryString::newFromInt($value, $minLength)->getData()
                );

            case 'EBCDIC':
                return $this->adjustOutputLength(
                    strtr(
                        $value,
                        '0123456789',
                        "\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9"
                    ),
                    "\xF0",
                    STR_PAD_LEFT
                );
        }
    }

    public function deserialize(string $input): LiteralInterface
    {
        if ($this->encoding_ == 'BCD') {
            $input = bin2hex($input);
        }

        $this->validateInputLength($input);

        switch ($this->encoding_) {
            case 'ASCII':
                $value = (int)$input;

            case 'BCD':
                $value = Bcd::newFromString($input)->toInt();

            case 'BIG-ENDIAN':
                $value = (new BinaryString($input))->toInt();
                break;

            case 'EBCDIC':
                $value = (int)strtr(
                    $input,
                    "\xF0\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9",
                    '0123456789'
                );
                break;
        }

        for (
            $type = $this->dataElement_->getDatatype();
            $type instanceof AbstractSimpleType;
            $type = $type->getBaseType()
        ) {
            $literalClass =
                $this->literalFactory_::DATATYPE_URI_TO_CLASS[$type->getUri()]
            ?? null;

            if (isset($literalClass)) {
                return new $literalClass(
                    $value,
                    $this->dataElement_->getDatatype()->getUri()
                );
            }
        }

        return new NonNegativeIntegerLiteral(
            $value,
            $this->dataElement_->getDatatype()->getUri()
        );
    }
}
