* create interface alcamo\rdfa\ConvertibleToInt
  * simplifies LiteralFactoryTest
* allow to call AbstractSerializer::__construct() with `null` data
  element, createing a default one fot the literal's favorite datatype
* implement other serializer classes
  * DateSerializer family with POSIX format for output and encodings
    ASCII, BCD, EBCDIC
* implement new literal classes (in WEAT repo?)
  * NumericStringLiteral
  * FourBitStringLiteral
* implement further serializers
  * NumericStringSerializer (encoding like
    NonNegativeIntegerSerializer plus COMPRESSED-BCD)
  * FourBitStringSerializer (encodings ASCII, FOUR-BIT)
