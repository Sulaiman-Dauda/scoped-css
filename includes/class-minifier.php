<?php
/**
 * CSS Minifier
 *
 * @package Scoped_CSS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Minifier class - minify and unminify CSS
 */
class Scoped_CSS_Minifier {

    /**
     * Minify CSS
     *
     * @param string $css Raw CSS.
     * @return string Minified CSS.
     */
    public static function minify( string $css ): string {
        if ( empty( $css ) ) {
            return '';
        }

        // Remove CSS comments /* ... */
        $css = preg_replace( '!/\*.*?\*/!s', '', $css );

        // Remove whitespace around { } : ; , > + ~
        $css = preg_replace( '/\s*([{};:,>+~])\s*/', '$1', $css );

        // Collapse multiple whitespace to single space
        $css = preg_replace( '/\s+/', ' ', $css );

        // Remove space after opening brace and before closing brace
        $css = str_replace( array( '{ ', ' }' ), array( '{', '}' ), $css );

        // Remove trailing semicolon before }
        $css = str_replace( ';}', '}', $css );

        // Remove newlines
        $css = str_replace( array( "\r\n", "\r", "\n" ), '', $css );

        return trim( $css );
    }

    /**
     * Attempt to unminify CSS (add formatting)
     *
     * @param string $css Minified CSS.
     * @return string Formatted CSS.
     */
    public static function unminify( string $css ): string {
        // Add newlines after opening braces
        $css = preg_replace( '/\{/', "{\n  ", $css );

        // Add newlines after closing braces
        $css = preg_replace( '/\}/', "\n}\n", $css );

        // Add newlines after semicolons in rules
        $css = preg_replace( '/;/', ";\n  ", $css );

        // Remove double spaces
        $css = preg_replace( '/\n\s+\n/', "\n", $css );

        // Clean up trailing whitespace on lines
        $css = preg_replace( '/[ \t]+(\n|$)/', '$1', $css );

        return trim( $css );
    }

    /**
     * Get minified size savings
     *
     * @param string $original Original CSS.
     * @param string $minified Minified CSS.
     * @return array { original: int, minified: int, saved: int, percent: float }
     */
    public static function calculate_savings( string $original, string $minified ): array {
        $original_size = strlen( $original );
        $minified_size = strlen( $minified );
        $saved = $original_size - $minified_size;
        $percent = $original_size > 0 ? round( ( $saved / $original_size ) * 100, 2 ) : 0;

        return array(
            'original'  => $original_size,
            'minified'  => $minified_size,
            'saved'     => $saved,
            'percent'   => $percent,
        );
    }
}
