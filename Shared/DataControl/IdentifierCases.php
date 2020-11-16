<?php
namespace Shared\DataControl;

use Shared\Exceptions\InvalidInputException;

/**
 * Contains helper functions for the conversion of identifier between case styles
 */
class IdentifierCases
{
    /*****************
     *   Constants   *
     ****************/

    // Styles
    public const CAMEL_CASE    = 1;
    public const CONSTANT_CASE = 5;
    public const KEBAB_CASE    = 3;
    public const PASCAL_CASE   = 4;
    public const SNAKE_CASE    = 2;

    // Additional source styles
    public const SPACE_CASE = 6;

    // Elements
    public const BASE_UPPERCASE       = 1;
    public const DASH_SEPARATED       = 16;
    public const FIRST_OTHER_CASE     = 4;
    public const OTHERS_OTHER_CASE    = 8;
    public const UNDERSCORE_SEPARATED = 32;

    /**
     * Implied defaults are base lowercase, no separator
     *
     * @var  int[]
     */
    private const ELEMENTS_PER_STYLE = [
        self::CAMEL_CASE    => self::OTHERS_OTHER_CASE,
        self::SNAKE_CASE    => self::UNDERSCORE_SEPARATED,
        self::KEBAB_CASE    => self::DASH_SEPARATED,
        self::PASCAL_CASE   => self::FIRST_OTHER_CASE | self::OTHERS_OTHER_CASE,
        self::CONSTANT_CASE => self::BASE_UPPERCASE | self::UNDERSCORE_SEPARATED,
    ];

    /**
     * @var  int[]
     */
    private const VALID_SOURCE_STYLES = [
        self::CAMEL_CASE,
        self::SNAKE_CASE,
        self::KEBAB_CASE,
        self::PASCAL_CASE,
        self::CONSTANT_CASE,
        self::SPACE_CASE
    ];


    /**********************
     *   Static methods   *
     *********************/

    /**
     * Convert between styles
     *
     * @param  string    $identifier
     * @param  int       $targetStyle
     * @param  int|null  $sourceStyle
     *
     * @return  string
     *
     * @throws  InvalidInputException
     */
    public static function convert(string $identifier, int $targetStyle, int $sourceStyle = null): string
    {
        if (!$sourceStyle) {
            $sourceStyle = self::determineStyle($identifier);
        }

        if (!in_array($sourceStyle, self::VALID_SOURCE_STYLES)) {
            throw new InvalidInputException($sourceStyle, 'source style');
        }

        if (!array_key_exists($targetStyle, self::ELEMENTS_PER_STYLE)) {
            throw new InvalidInputException($targetStyle, 'target style');
        }

        $words = self::identifierToWords($identifier, $sourceStyle);

        // Gather target style elements
        $elements        = self::ELEMENTS_PER_STYLE[$targetStyle];
        $baseUpperCase   = $elements & self::BASE_UPPERCASE;
        $firstUpperCase  = $elements & self::FIRST_OTHER_CASE ?  !$baseUpperCase : $baseUpperCase;
        $othersUpperCase = $elements & self::OTHERS_OTHER_CASE ? !$baseUpperCase : $baseUpperCase;

        $separator = $elements & self::DASH_SEPARATED
            ? '-'
            : ($elements & self::UNDERSCORE_SEPARATED ? '_' : '');

        // Process each word
        $newWords = [];

        foreach ($words as $i => $word) {
            // Apply base case
            $word = $baseUpperCase ? mb_strtoupper($word) : mb_strtolower($word);

            // Apply initial character case
            if (($i == 0 && $firstUpperCase) || ($i > 0 && $othersUpperCase)) {
                $word = mb_strtoupper(substr($word, 0, 1)) . substr($word, 1);
            }

            $newWords[] = $word;
        }

        // Apply separators
        return implode($separator, $newWords);
    }

    /**
     * Determine the style of the given identifier
     *
     * @param  string  $identifier
     *
     * @return  int
     */
    public static function determineStyle(string $identifier): int
    {
        if (false !== strpos($identifier, '-')) {
            return self::KEBAB_CASE;
        }

        if (false !== strpos($identifier, '_')) {
            return preg_match('/[a-z]/', $identifier) ? self::SNAKE_CASE : self::CONSTANT_CASE;
        }

        if (false !== strpos($identifier, ' ')) {
            return self::SPACE_CASE;
        }

        return preg_match('/^[a-z]/', $identifier) ? self::CAMEL_CASE : self::PASCAL_CASE;
    }

    /**
     * Turns the given identifier into an array of words
     *
     * @param  string    $identifier
     * @param  int|null  $sourceStyle
     *
     * @return  string[]|false
     *
     * @throws  InvalidInputException
     */
    public static function identifierToWords(string $identifier, int $sourceStyle = null)
    {
        if (!$sourceStyle) {
            $sourceStyle = self::determineStyle($identifier);
        }

        if (!in_array($sourceStyle, self::VALID_SOURCE_STYLES)) {
            throw new InvalidInputException($sourceStyle, 'source style');
        }

        if ($sourceStyle === self::KEBAB_CASE) {
            return explode('-', $identifier);
        }

        if ($sourceStyle === self::SNAKE_CASE || $sourceStyle === self::CONSTANT_CASE) {
            return explode('_', $identifier);
        }

        if ($sourceStyle === self::SPACE_CASE) {
            return explode(' ', $identifier);
        }

        $words = [];
        $word  = '';

        for ($p = 0; $p < strlen($identifier); ++$p) {
            if ($p === 0) {
                $word = $identifier[$p];

                continue;
            }

            if (preg_match('/[A-Z]/', $identifier[$p])) {
                $words[] = $word;
                $word    = '';
            }

            $word .= $identifier[$p];
        }

        if ($word) {
            $words[] = $word;
        }

        return $words;
    }

    /**
     * Turn the given identifier to camelCase
     *
     * @param  string    $identifier
     * @param  int|null  $sourceStyle
     *
     * @return  string
     */
    public static function toCamelCase(string $identifier, int $sourceStyle = null): string
    {
        return self::convert($identifier, self::CAMEL_CASE, $sourceStyle);
    }

    /**
     * Turn the given identifier to CONSTANT_CASE
     *
     * @param  string    $identifier
     * @param  int|null  $sourceStyle
     *
     * @return  string
     */
    public static function toConstantCase(string $identifier, int $sourceStyle = null): string
    {
        return self::convert($identifier, self::CONSTANT_CASE, $sourceStyle);
    }

    /**
     * Turn the given identifier to kebab-case
     *
     * @param  string    $identifier
     * @param  int|null  $sourceStyle
     *
     * @return  string
     */
    public static function toKebabCase(string $identifier, int $sourceStyle = null): string
    {
        return self::convert($identifier, self::KEBAB_CASE, $sourceStyle);
    }

    /**
     * Turn the given identifier to PascalCase
     *
     * @param  string    $identifier
     * @param  int|null  $sourceStyle
     *
     * @return  string
     */
    public static function toPascalCase(string $identifier, int $sourceStyle = null): string
    {
        return self::convert($identifier, self::PASCAL_CASE, $sourceStyle);
    }

    /**
     * Turn the given identifier to snake_case
     *
     * @param  string    $identifier
     * @param  int|null  $sourceStyle
     *
     * @return  string
     */
    public static function toSnakeCase(string $identifier, int $sourceStyle = null): string
    {
        return self::convert($identifier, self::SNAKE_CASE, $sourceStyle);
    }
}
