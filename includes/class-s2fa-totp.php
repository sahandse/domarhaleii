<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class S2FA_TOTP {
    const PERIOD = 30;
    const DIGITS = 6;

    public static function generate_secret( $length = 20 ) {
        return self::base32_encode( random_bytes( $length ) );
    }

    public static function verify( $secret, $code, $window = 1 ) {
        $code = preg_replace( '/\D+/', '', (string) $code );
        if ( strlen( $code ) !== self::DIGITS ) { return false; }
        $counter = (int) floor( time() / self::PERIOD );
        for ( $i = -absint( $window ); $i <= absint( $window ); $i++ ) {
            if ( hash_equals( self::code( $secret, $counter + $i ), $code ) ) { return true; }
        }
        return false;
    }

    public static function code( $secret, $counter ) {
        $key = self::base32_decode( $secret );
        if ( false === $key ) { return ''; }
        $binary_counter = pack( 'N*', 0 ) . pack( 'N*', $counter );
        $hash = hash_hmac( 'sha1', $binary_counter, $key, true );
        $offset = ord( substr( $hash, -1 ) ) & 0x0F;
        $bin = ( ( ord( $hash[$offset] ) & 0x7F ) << 24 ) |
               ( ( ord( $hash[$offset + 1] ) & 0xFF ) << 16 ) |
               ( ( ord( $hash[$offset + 2] ) & 0xFF ) << 8 ) |
               ( ord( $hash[$offset + 3] ) & 0xFF );
        return str_pad( (string) ( $bin % ( 10 ** self::DIGITS ) ), self::DIGITS, '0', STR_PAD_LEFT );
    }

    public static function provisioning_uri( $secret, $account, $issuer ) {
        $label = rawurlencode( $issuer . ':' . $account );
        return 'otpauth://totp/' . $label . '?secret=' . rawurlencode( $secret ) . '&issuer=' . rawurlencode( $issuer ) . '&period=' . self::PERIOD . '&digits=' . self::DIGITS;
    }

    private static function base32_encode( $data ) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach ( str_split( $data ) as $char ) { $bits .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT ); }
        $output = '';
        foreach ( str_split( $bits, 5 ) as $chunk ) {
            $chunk = str_pad( $chunk, 5, '0', STR_PAD_RIGHT );
            $output .= $alphabet[ bindec( $chunk ) ];
        }
        return $output;
    }

    private static function base32_decode( $data ) {
        $alphabet = array_flip( str_split( 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567' ) );
        $data = strtoupper( preg_replace( '/[^A-Z2-7]/', '', (string) $data ) );
        $bits = '';
        foreach ( str_split( $data ) as $char ) {
            if ( ! isset( $alphabet[$char] ) ) { return false; }
            $bits .= str_pad( decbin( $alphabet[$char] ), 5, '0', STR_PAD_LEFT );
        }
        $output = '';
        foreach ( str_split( $bits, 8 ) as $byte ) {
            if ( strlen( $byte ) === 8 ) { $output .= chr( bindec( $byte ) ); }
        }
        return $output;
    }
}
