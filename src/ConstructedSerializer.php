<?php

namespace alcamo\data_element;

use alcamo\collection\ReadonlyCollectionTrait;
use alcamo\dom\schema\component\SimpleTypeInterface;
use alcamo\exception\{DataValidationFailed, Eof, InvalidType, SyntaxError};
use alcamo\range\NonNegativeRange;
use alcamo\rdf_literal\{HexBinaryLiteral, LiteralInterface};

/**
 * @brief (De)Serializer for constructed data
 *
 * @date Last reviewed 2026-03-02
 */
class ConstructedSerializer extends AbstractSerializer implements
    \Countable,
    \ArrayAccess,
    \Iterator
{
    use ReadonlyCollectionTrait;

    public const SUPPORTED_DATATYPE_XNAMES = [ self::XSD_NS . ' string' ];

    public const DEFAULT_ENCODING = 'BINARY';

    private $separator_; ///< ?string

    public static function newFromProps(object $props): SerializerInterface
    {
        return new static(
            $props->serializers ?? null,
            $props->separator ?? null,
            $props->lengthRange ?? null,
            $props->flags ?? null
        );
    }

    /**
     * @parm $serializers Iterable of SerializerInterface objects
     *
     * @parm $separator String to separate items in (de)serialization.
     *
     * @param $lengthRange Allowed length of serialized data, in bytes.
     *
     * @param $flags Bitwise-OR-combination of the constants in
     * alcamo::data_element::SerializerInterface.
     *
     * Padding string and padding type are taken from the last serializer.
     */
    public function __construct(
        iterable $serializers,
        ?string $separator = null,
        ?NonNegativeRange $lengthRange = null,
        ?int $flags = null
    ) {
        foreach ($serializers as $key => $serializer) {
            if (!($serializer instanceof SerializerInterface)) {
                /** @throw alcamo::exception::InvalidType if an item in
                 *  $serializers is not a SerializerInterface object. */
                throw (new InvalidType())->setMessageContext(
                    [
                        'value' => $serializer,
                        'expectedOneOf' => SerializerInterface::class
                    ]
                );
            }

            $this->data_[$key] = $serializer;
        }

        parent::__construct(
            self::XSD_NS . ' string',
            $lengthRange,
            $serializer->padString_,
            $serializer->padType_,
            $flags,
            static::DEFAULT_ENCODING,
            $serializer->literalWorkbench_
        );

        $this->separator_ = $separator;
    }

    public function serialize(LiteralInterface $literal): string
    {
        if (!($literal instanceof ConstructedLiteral)) {
            /** @throw alcamo::exception::InvalidType if $literal is not
             *  ConstructedLiteral. */
            throw (new InvalidType())->setMessageContext(
                [
                    'type' => get_class($literal),
                    'extraMessage' => 'incompatible with ' . static::class
                ]
            );
        }

        if (!($this->flags_ & self::TRUNCATE_SILENTLY)) {
            if (count($literal) != count($this)) {
                /** @todo throw alcamo::exception::DataValidationFailed if
                 *  literal count does not match serializer count and the
                 *  TRUNCATE_SILENTLY flag is not set. */
                throw (new DataValidationFailed())->setMessageContext(
                    [
                        'extraMessage' => 'literal count ' . count($literal)
                            . ' does not match serializer count '
                            . count($this)
                    ]
                );
            }
        }

        $this->rewind();

        foreach ($literal as $item) {
            if (isset($result)) {
                $result .= $this->separator_
                    . (isset($item) ? $this->current()->serialize($item) : '');
            } else {
                $result =
                    isset($item) ? $this->current()->serialize($item) : '';
            }

            $this->next();

            if (!$this->valid()) {
                break;
            }
        }

        return $this->adjustOutputLength($result);
    }

    public function deserialize(
        string $input,
        ?SimpleTypeInterface $datatype = null
    ): LiteralInterface {
        $this->validateInputLength($input);

        $result = [];
        $pos = 0;

        /**
         * If the input ends exactly after application of a deserializer and
         * $flags contain TRUNCATE_SILENTLY, accept this gracefully.
         *
         * @throw alcamo::exception::Eof if input ends before all
         * deserializers have been applied and either $flags do not contain
         * TRUNCATE_SILENTLY or there are data left ot read.
         */
        if (isset($this->separator_)) {
            /** If there is a separator defined, read up to the separator for
             *  each field and apply a deserializer. */
            foreach ($this as $key => $serializer) {
                if (!isset($input[$pos])) {
                    if ($this->flags_ & self::TRUNCATE_SILENTLY) {
                        break;
                    }

                    throw (new Eof('Failed to read from {object}'))
                        ->setMessageContext(
                            [
                                'object' => $input,
                                'atOffset' => $pos,
                                'forKey' => $key
                            ]
                        );
                }

                $pos2 = strpos($input, $this->separator_, $pos);

                $length = $pos2 === false
                    ? strlen($input) - $pos
                    : $pos2 - $pos;

                $result[] = $length
                    ? $serializer->deserialize(substr($input, $pos, $length))
                    : null;

                $pos += $length + strlen($this->separator_);
            }
        } else {
            /** If there is no separator defined, read the minimum length for
             *  each deserializer. */
            foreach ($this as $key => $serializer) {
                if (
                    !isset($input[$pos])
                        && $this->flags_ & self::TRUNCATE_SILENTLY
                ) {
                    break;
                }

                $length = $serializer->getLengthRange()->getMin();

                if (strlen($input) < $pos + $length) {
                    throw (new Eof())->setMessageContext(
                        [
                            'object' => $input,
                            'requestedUnits' => $length,
                            'atOffset' => $pos,
                            'forKey' => $key
                        ]
                    );
                }

                $result[] =
                    $serializer->deserialize(substr($input, $pos, $length));

                $pos += $length;
            }
        }

        if (
            $pos < strlen($input) && !($this->flags_ & self::TRUNCATE_SILENTLY)
        ) {
            /**
             * @throw alcamo::exception::SyntaxError if there is input left
             * after all deserializers have been applied and $flags do not
             * contain TRUNCATE_SILENTLY.
             */
            throw (new SyntaxError())->setMessageContext(
                [
                    'inData' => $input,
                    'atOffset' => $pos,
                    'extraMessage' => 'spurious trailing data'
                ]
            );
        }

        return new Constructedliteral($result, $datatype);
    }
}
