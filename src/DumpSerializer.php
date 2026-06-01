<?php

namespace alcamo\data_element;

use alcamo\binary_data\BinaryString;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\dom\schema\TypeMap;
use alcamo\input_stream\StringInputStream;
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{ConstructedLiteral, LiteralInterface};
use alcamo\exception\SyntaxError;

/**
 * @brief (De)Serializer using dump() / dedump()
 *
 * @date Last reviewed 2026-04-21
 */
class DumpSerializer implements SerializerInterface
{
    /**
     * @brief Map of XSD type XNames to serializer classes
     *
     * Any other type will be mapped to a StringSerializer.
     */
    public const TYPE_XNAME_TO_SERIALIZER_CLASS = [
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

    public static function newFromProps($props): SerializerInterface
    {
        $props = (object)$props;

        return new static(
            $props->flags ?? null,
            $props->separator ?? null,
            $props->deWorkbench ?? null
        );
    }

    protected $flags_;            ///< int
    protected $deWorkbench_;      ///< DeWorkbench

    private $typeToSerializer_;   ///< TypeMap
    private $binarySerializer_;   ///< BinarySerializer
    private $dateTimeSerializer_; ///< DateTimeSerializer
    private $integerSerializer_;  ///< IntegerSerializer
    private $stringSerializer_;   ///< StringSerializer
    private $separator_;          ///< ?string

    /**
     * @param $flags Bitwise-OR-combination of the constants in
     * alcamo::data_element::SerializerInterface. Currently the flags have no
     * effect.
     *
     * @param $separator String to separate items in (de)serialization for
     * constructed literals. [default one space in serialization and any
     * whitespace in deserialization]
     *
     * @param $deWorkbench Workbench used in deserialize() and in
     * validateLiteralClass(). [default
     * alcamo::data_element::DeWorkbench::getMainInstance()]
     */
    public function __construct(
        ?int $flags = null,
        ?string $separator = null,
        ?DeWorkbench $deWorkbench = null
    ) {
        $this->flags_ = (int)$flags;

        $this->separator_ = $separator;

        $this->deWorkbench_ =
            $deWorkbench ?? DeWorkbench::getMainInstance();

        $typeXNameToSerializer = [];

        foreach (
            static::TYPE_XNAME_TO_SERIALIZER_CLASS as $typeXName => $serializerClass
        ) {
            $typeXNameToSerializer[$typeXName] = $serializerClass::newFromProps(
                [
                    'datatypeXName' => $typeXName,
                    'deWorkbench' => $this->deWorkbench_
                ]
            );
        }

        $this->binarySerializer_ =
            $typeXNameToSerializer[self::XSD_NS . ' hexBinary'];

        $this->dateTimeSerializer_ =
            $typeXNameToSerializer[self::XSD_NS . ' dateTime'];

        $this->integerSerializer_ =
            $typeXNameToSerializer[self::XSD_NS . ' integer'];

        $this->stringSerializer_ = StringSerializer::newFromProps(
            [ 'deWorkbench' => $this->deWorkbench_ ]
        );

        $this->typeToSerializer_ =
            new TypeMap($typeXNameToSerializer, $this->stringSerializer_);
    }

    public function getDatatype(): SimpleTypeInterface
    {
        return $this->deWorkbench_->getSchema()->getAnySimpleType();
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

    public function getDeWorkbench(): DeWorkbench
    {
        return $this->deWorkbench_;
    }

    public function getSeparator(): ?string
    {
        return $this->separator_;
    }

    public function serialize(LiteralInterface $literal): string
    {
        return $this->dump($literal);
    }

    public function serializeToHex(LiteralInterface $literal): string
    {
        return strtoupper(bin2hex($this->serialize($literal)));
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        return $this->dedump($input, $datatype);
    }

    public function deserializeFromHex(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        return $this->deserialize(hex2bin($input), $datatype);
    }

    public function dump(LiteralInterface $literal): string
    {
        if (!($literal instanceof ConstructedLiteral)) {
            return $this->typeToSerializer_
                ->lookup($this->deWorkbench_->validateLiteral($literal))
                ->dump($literal);
        }

        /* Serialize a constructed literal. */

        $separator = $this->separator_ ?? ' ';

        $result = "[$separator";

        foreach ($literal as $item) {
            $result .= $this->dump($item) . $separator;
        }

        return $result . ']';
    }

    public function dedump(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        if ($input[0] != '[' && isset($datatype)) {
            return $this->typeToSerializer_->lookup($datatype)
                ->dedump($input, $datatype);
        }

        /**
         * If no $datatype is provided or the first character is an opening
         * bracket, the serializer to apply is guessed from the first
         * character of $input:
         * - " => StringSerializer
         * - ' => BinarySerializer
         * - [ => constructed
         * - repetition of a string => StringSerializer
         * - repetition of a binary => BinarySerializer
         * - all digits, optionally preceded by a minus sign =>
         *   IntegerSerializer
         * - else DateTimeSerializer, assuming a complete ISO 8601 date and time
         */
        switch ($input[0]) {
            case '"':
                return $this->stringSerializer_->dedump($input);

            case "'":
                return $this->binarySerializer_->dedump($input);

            case '[':
                break;

            default:
                switch (true) {
                    case $input[0] == '-' && ctype_digit(substr($input, 1))
                        || ctype_digit($input):
                        return $this->integerSerializer_
                            ->dedump($input, $datatype);

                    case preg_match(
                        '/^(\d+)\s*\*\s*"([^"]+)"$/',
                        $input,
                        $matches
                    ):
                        return $this->deWorkbench_->createLiteral(
                            str_repeat($matches[2], $matches[1]),
                            $this->stringSerializer_->getDatatype()
                        );

                    case preg_match(
                        '/^(\d+)\s*\*\s*\'([^\']+)\'$/',
                        $input,
                        $matches
                    ):
                        return $this->deWorkbench_->createLiteral(
                            BinaryString::newFromHex(
                                str_repeat($matches[2], $matches[1])
                            ),
                            $this->binarySerializer_->getDatatype()
                        );

                    default:
                        return $this->dateTimeSerializer_->dedump($input);
                }
        }

        /* Deserialize a constructed literal. */

        if ($input[-1] != ']') {
            /** @throw alcamo::exception::SyntaxError on attempt to
             *  deserialize a constructed input not terminated by a
             *  bracket. */
            throw (new SyntaxError())->setMessageContext(
                [
                    'inData' => $input,
                    'extraMessage' => "not terminated by \"]\""
                ]
            );
        }

        /* Data without brackets. */
        $stream = new StringInputStream(substr($input, 1, strlen($input) - 2));

        /* If separator is whitespace, skip optional whitespace after opening
         * bracket. */
        if (!isset($this->separator_)) {
            $stream->extractWs();
        }

        $result = [];

        while ($item = $stream->extractToken($this->separator_, true)) {
            $result[] = $item == '' ? null : $this->dedump($item);
        }

        return new Constructedliteral($result, $datatype);
    }
}
