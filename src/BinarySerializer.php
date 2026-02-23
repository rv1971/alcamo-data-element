<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdfa\LiteralInterface;

class BinarySerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        [ self::XSD_NS, 'anyURI' ],
        [ self::XSD_NS, 'base64Binary' ],
        [ self::XSD_NS, 'hexBinary' ],
        [ self::XSD_NS, 'NOTATION' ],
        [ self::XSD_NS, 'QName' ],
        [ self::XSD_NS, 'string' ]
    ];

    public const SUPPORTED_LITERAL_CLASSES = [
        Base64BinaryLiteral::class,
        HexBinaryLiteral::class,
        LangStringLiteral::class,
        StringLiteral::class
    ];

    public const DEFAULT_DATATYPE_URI = HexBinaryLiteral::DATATYPE_URI;

    public function __construct(
        ?DataElementInterface $dataElement = null,
        ?NonNegativeRange $lengthRange = null
        ?int $flags = null
    ) {
        parent::__construct(
            $dataElement ?? new DataElement(
                (new SchemaFactory())
                    ->createTypeFromUri(static::DEFAULT_DATATYPE_URI)
            ),
            $lengthRange,
            $flags
        );
    }

    public function serialize(?LiteralInterface $literal = null): string
    {
        if (isset($literal)) {
            $this->validateLiteralClass($literal);
        } else {
            $literal = $this->dataElement_->createLiteral();
        }

        $value = $literal->getValue();

        return $this->adjustOutputLength(
            $value instanceof BinaryString
            ? $value->getData()
            : (string)$value,
            "\x00"
        );
    }

    public function deserialize(string $input): LiteralInterface
    {
        $this->validateInputLength($input);

        return new HexBinaryLiteral(
            $input,
            $this->dataElement_->getDatatype()->getUri()
        );
    }
}
