<?php

namespace alcamo\data_element;

use alcamo\rdfa\LiteralInterface;

class StringSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATA_TYPE_XNAMES = [
        [ self::XSD_NS, 'anyURI' ],
        [ self::XSD_NS, 'NOTATION' ],
        [ self::XSD_NS, 'QName' ],
        [ self::XSD_NS, 'string' ]
    ];

    public const SUPPORTED_LITERAL_CLASSES = [
        LangStringLiteral::class,
        StringLiteral::class
    ];

    public function __construct(
        DataElementInterface $dataElement,
        ?NonNegativeRange $lengthRange = null
        ?int $flags = null
    ) {
        parent::__construct(
            $dataElement,
            ExtentRange::newFromNonNegativeRange($lengthRange),
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
