<?php

namespace alcamo\data_element;

use alcamo\range\NonNegativeRange;
use alcamo\rdfa\LiteralInterface;

class StringSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATATYPE_XNAMES = [
        [ self::XSD_NS, 'anyURI' ],
        [ self::XSD_NS, 'NOTATION' ],
        [ self::XSD_NS, 'QName' ],
        [ self::XSD_NS, 'string' ]
    ];

    public const DEFAULT_DATATYPE_URI = StringLiteral::DATATYPE_URI;

    public const SUPPORTED_LITERAL_CLASSES = [
        LangStringLiteral::class,
        StringLiteral::class
    ];

    public function serialize(?LiteralInterface $literal = null): string
    {
        if (isset($literal)) {
            $this->validateLiteralClass($literal);
        } else {
            $literal = $this->dataElement_->createLiteral();
        }

        return $this->adjustOutputLength($literal->getValue(), ' ');
    }

    public function deserialize(string $input): LiteralInterface
    {
        $this->validateInputLength($input);

        return new StringLiteral(
            $input,
            $this->dataElement_->getDatatype()->getUri()
        );
    }
}
