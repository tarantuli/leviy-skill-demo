<?php
namespace Shared\DataControl;

/**
 * (no summary)
 */
class Regex
{
    /*****************
     *   Constants   *
     ****************/

    // Memetics
    public const NO_LIMIT = -1;


    /************************
     *   Static variables   *
     ***********************/

    /**
     * @var  string
     */
    private static $defaultModifiers = '';


    /**********************
     *   Static methods   *
     *********************/

    /**
     * @param  array|string  $pattern
     * @param  string        $subject
     *
     * @return  array
     */
    public static function getMatch($pattern, string $subject): array
    {
        $pattern = self::normalizePattern($pattern);
        $match   = [];

        preg_match($pattern, $subject, $match);

        return $match;
    }

    /**
     * @param  array|string  $pattern
     * @param  string        $subject
     *
     * @return  array[]
     */
    public static function getMatchedSets($pattern, string $subject): array
    {
        $pattern = self::normalizePattern($pattern);
        $matches = [];

        preg_match_all($pattern, $subject, $matches, PREG_SET_ORDER);

        return $matches;
    }

    /**
     * @param  array|string  $pattern
     * @param  string        $subject
     *
     * @return  array[]
     */
    public static function getMatches($pattern, string $subject): array
    {
        $pattern = self::normalizePattern($pattern);
        $matches = [];

        preg_match_all($pattern, $subject, $matches);

        return $matches;
    }

    /**
     * @param  array|string  $pattern
     * @param  string        $subject
     *
     * @return  bool
     */
    public static function matches($pattern, string $subject): bool
    {
        $pattern = self::normalizePattern($pattern);

        return preg_match($pattern, $subject);
    }

    /**
     * @param  array|string     $pattern
     * @param  callable|string  $replacement
     * @param  string           &$subject
     *
     * @return  int
     */
    public static function replace($pattern, $replacement, & $subject): int
    {
        $pattern = self::normalizePattern($pattern);
        $count   = 0;

        if (is_callable($replacement)) {
            $subject = preg_replace_callback($pattern, $replacement, $subject, self::NO_LIMIT, $count);
        }
        else {
            $subject = preg_replace($pattern, $replacement, $subject, self::NO_LIMIT, $count);
        }

        return $count;
    }

    /**
     * @param  bool  $toggle
     *
     * @return  void
     */
    public static function utf8Mode(bool $toggle = true): void
    {
        if ($toggle) {
            if (false === strpos(self::$defaultModifiers, 'u')) {
                self::$defaultModifiers .= 'u';
            }
        }
        else {
            self::$defaultModifiers = str_replace('u', '', self::$defaultModifiers);
        }
    }

    /**
     * @param  array|string  $pattern
     *
     * @return  string
     */
    private static function normalizePattern($pattern): string
    {
        if (is_array($pattern)) {
            [$pattern, $modifiers] = $pattern;
        }
        else {
            $modifiers = '';
        }

        $modifiers .= self::$defaultModifiers;

        return sprintf('#%s#%s', str_replace('#', '\\#', $pattern), $modifiers);
    }
}
