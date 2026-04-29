<?php

namespace alcamo\data_element;

use alcamo\dom\schema\TypeMap;
use alcamo\exception\SyntaxError;

class DumpSerializer implements SerializerInterface
{
    /**
     * @brief Map of XSD type XNames to serializer classes
     *
     * Any other type will be mapped to a StringSerializer.
     */
    protected const TYPE_XNAME_TO_SERIALIZER_CLASS = [
        self::XSD_NS . ' base64Binary'  => BinarySerializer::class,
        self::XSD_NS . ' hexBinary'     => BinarySerializer::class,
        self::XSD_NS . ' date'          => DateTimeSerializer::class,
        self::XSD_NS . ' dateTime'      => DateTimeSerializer::class,
        self::XSD_NS . ' gDay'          => DateTimeSerializer::class,
        self::XSD_NS . ' gMonth'        => DateTimeSerializer::class,
        self::XSD_NS . ' gMonthDay'     => DateTimeSerializer::class,
        self::XSD_NS . ' gYear'         => DateTimeSerializer::class,
        self::XSD_NS . ' gYearMonth'    => DateTimeSerializer::class,
        self::XSD_NS . ' time'          => DateTimeSerializer::class,
        self::XSD_NS . ' boolean'       => IntegerSerializer::class,
        self::XSD_NS . ' integer'       => IntegerSerializer::class
    ];

    protected $flags_;            ///< int
    protected $literalWorkbench_; ///< LiteralWorkbench

    private $typeToSerializer_;   ///< TypeMap
    private $binarySerializer_;   ///< BinarySerializer
    private $dateTimeSerializer_; ///< DateTimeSerializer
    private $integerSerializer_;  ///< IntegerSerializer
    private $stringSerializer_;   ///< StringSerializer

    /**
     * @param $flags Bitwise-OR-combination of the constants in
     * alcamo::data_element::SerializerInterface. Currently the flags have no
     * effect.
     *
     * @param $literalWorkbench Workbench used in deserialize() and in
     * validateLiteralClass(). [default
     * alcamo::data_element::LiteralWorkbench::getMainInstance()]
     */
    public function __construct(
        ?int $flags = null,
        ?LiteralWorkbench $literalWorkbench = null
    ) {
        $this->flags_ = (int)$flags;

        $this->literalWorkbench_ =
            $literalWorkbench ?? LiteralWorkbench::getMainInstance();

        $typeXNameToSerializer = [];

        $simpleSerializerFlags = $this->flags_ & ~self::TRUNCATE_SILENTLY;

        foreach (
            static::TYPE_XNAME_TO_SERIALIZER_CLASS
                as $typeXName => $serializerClass
        ) {
            $typeXNameToSerializer[$typeXName] = $serializerClass::newFromProps(
                [
                    'datatypeXName' => $typeXName,
                    'encoding' => 'DUMP',
                    'flags' => $this->flags_,
                    'literalWorkbench' => $this->literalWorkbench_
                ]
            );
        }

        $this->binarySerializer_ =
            $typeXNameToSerializer[self::XSD_NS . ' hexBinary'];

        $this->stringSerializer_ = StringSerializer::newFromProps(
            [
                'encoding' => 'DUMP',
                'flags' => $this->flags_,
                'literalWorkbench' => $this->literalWorkbench_
            ]
        );

        $this->typeToSerializer_ =
            new TypeMap($typeXNameToSerializer, $this->stringSerializer_);
    }

    public function getDatatype(): SimpleTypeInterface
    {
        return $this->literalWorkbench_->getSchema()->getAnySimpleType();
    }

    public function getEncoding(): string
    {
        return 'DUMP';
    }

    public function getLengthRange(): ?NonNegativeRange
    {
        return null;
    }

    public function getFlags(): int
    {
        return $this->flags_;
    }

    public function getPadString(): string
    {
        return '';
    }

    public function getPadType(): int
    {
        return 0;
    }

    public function getLiteralWorkbench(): LiteralWorkbench
    {
        return $this->literalWorkbench_;
    }

    public function serialize(LiteralInterface $literal): string
    {
        if (!(LiteralInterface instanceof ConstructedLiteral)) {
            $this->typeToSerializer_
                ->lookup($this->workbench_->validateLiteral($literal))
                ->serialize($literal);
        }

        /* serialize a constructed literal */

        $result = '[ ';

        foreach ($literal as $item) {
            $result .= $this->serialize($item) . ' ';
        }

        return $result . ']';
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface
    {
        if ($input[0] != '[' && isset($datatype)) {
            return $this->typeToSerializer_->lookup($datatype)
                ->deserialize($input, $datatype);
        }

        /**
         * If no $datatype is provided, the serializer to apply is guessed
         * from the first character of $input:
         * - " => StringSerializer
         * - ' => BinarySerializer
         * - [ => constructed
         * - all digits, optionally preceded by a minus sign =>
         *   IntegerSerializer
         * - else DateTimeSerializer, assuming a complete ISO 8601 date and time
         */
        switch ($input[0]) {
            case '"':
                return $this->stringSerializer_->deserialize($input);

            case "'":
                return $this->binarySerializer_->deserialize($input);

            case '[':
                break;

            default:
                if (
                    $input[0] == '-' && ctype_digit(substr($input, 1))
                        || ctype_digit($input)
                ) {
                    return $this->integerSerializer_->deserialize($input);
                } else {
                    return $this->dateTimeSerializer_->deserialize($input);
                }
        }

        /* deserialize a constructed literal */

        if (substr($input, 0, 2) != '[ ' || substr($input, 0, -2) != ' ]') {
            /** @throw alcamo::exception::SyntaxError on attempt to
             *  deserialize an input which is not surrounded by a separator
             *  and brackets. */
            throw (new SyntaxError())->setMessageContext(
                [
                    'inData' => $input,
                    'extraMessage' => "not surrounded by \"[ \" and \" ]\""
                ]
            );
        }

        /* Data without left bracket and space and without right bracket but
         * with right space, so that the last item needs no special
         * handling. */
        $stream = new StringInputStream(
            substr($input, 2, strlen($input) - 3)
        );

        $result = [];

        while ($item = $stream->extractToken(' ')) {
            $result[] = $this->unserialize($item);

            $separator = $stream->extract();

            if ($separator != ' ') {
                /** @throw alcamo::exception::SyntaxError on attempt to
                 *  deserialize an input where a separator is wrong. */
                throw (new SyntaxError())->setMessageContext(
                    [
                        'inData' => $input,
                        'atOffset' => 2 + $stream->getOffset() - 1,
                        'extraMessage' =>
                            "expected space, found \"$separator\""
                    ]
                );
            }
        }

        return new Constructedliteral($result, $datatype);
    }
}
