<?php

namespace alcamo\data_element;

use alcamo\binary_data\{Bcd, BinaryString};
use alcamo\exception\{InvalidEnumerator, OutOfRange};
use alcamo\rdfa\LiteralInterface;

class NonNegativeIntegerSerializer extends AbstractSerializer
{
    public const SUPPORTED_DATA_TYPE_XNAMES = [
        [ self::XSD_NS, 'boolean' ],
        [ self::XSD_NS, 'gDay' ],
        [ self::XSD_NS, 'gMonth' ],
        [ self::XSD_NS, 'gYear' ],
        [ self::XSD_NS, 'nonNegativeInteger' ]
    ];

    public const SUPPORTED_LITERAL_CLASSES = [
        BooleanLiteral::class,
        GDayLiteral::class,
        GMonthLiteral::class,
        GYearLiteral::class,
        IntegerLiteral::class
    ];

    public const ENCODING_TO_X12_UNIT = [
        'ASCII'      => 'AD',
        'BCD'        => '06',
        'BIG-ENDIAN' => 'AD',
        'EBCDIC'     => 'AD'
    ];

    public const DEFAULT_ENCODING = 'ASCII';

    priuvate $encoding_; ///< string

    public function __construct(
        DataElementInterface $dataElement,
        ?NonNegativeRange $lengthRange = null
        ?int $flags = null,
        ?string $encoding = null,
        ?LiteralFactory $literalFactory = null;
    ) {
        if (!isset($encoding)) {
            $encoding = static::DEFAULT_ENCODING;
        }

        if (!isset(static::ENCODING_TO_X12_UNIT[$encoding])) {
            /** @throw alcamo::exception::InvalidEnumerator if $encoding is
             *  not a valid encoding. */
            throw (new InvalidEnumerator())->setMessageContext(
                [
                    'value' => $encoding,
                    'expectedOneOf' => static::ENCODINGS
                ]
            );
        }

        parent::__construct(
            $dataElement,
            ExtentRange::newFromNonNegativeRange(
                $lengthRange,
                static::ENCODING_TO_X12_UNIT[$encoding]
            ),
            $flags
        );

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

        if ($literal instanceof IntegerLiteral) {
            OutOfRange::throwIfOutside($value, 0);
        }

        [ $minLength, $maxLength ] = isset($this->extentRange_)
            ? $this->extentRange_->getMinMax()
            : [ null, null ];

        switch ($this->encoding_) {
            case 'ASCII':
                return $this->adjustOutputLength($value, '0', STR_PAD_LEFT);

            case 'BCD':
                return hex2bin(
                    $this->adjustOutputLength(
                        Bcd::newFromInt($value, $minLength)
                    )
                );

            case 'BIG-ENDIAN':
                return $this->adjustOutputLength(
                    BinaryString::newFromInt($value, $minLength)->getData()
                );

            case 'EBCDIC':
                $result = '';

                $value = (string)$value;

                for ($i = 0; isset($value[$i]); $i++) {
                    $result .= "F{$value[$i]}";
                }

                return $this->adjustOutputLength(
                    hex2bin($result),
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
                $value = (int)hex2bin(strtr(bin2hex($input), 'f', '3'));
                break;
        }

        $literalClass = $this->literalFactory_::DATATYPE_URI_TO_CLASS[
            $this->dataElement_->getDatatype()->getPrimitiveType()->getUri()
        ] ?? IntegerLiteral::class;

        return new $literalClass(
            $value,
            $this->dataElement_->getDatatype()->getUri()
        );
    }
}
