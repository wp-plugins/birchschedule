<?php

function birchbase_util_def() {

    $ns = birch_ns( 'birchbase.util' );

    birch_defn( $ns, 'date_i18n', function( $dateformatstring, $unixtimestamp ) use ( $ns ) {
            global $wp_locale;
            $datetime = $ns->get_wp_datetime( $unixtimestamp );
            if ( ( !empty( $wp_locale->month ) ) && ( !empty( $wp_locale->weekday ) ) ) {
                $datemonth = $datetime->format( 'm' );
                $datemonth = $wp_locale->get_month( $datemonth );
                $datemonth_abbrev = $wp_locale->get_month_abbrev( $datemonth );

                $dateweekday = $datetime->format( 'w' );
                $dateweekday = $wp_locale->get_weekday( $dateweekday );
                $dateweekday_abbrev = $wp_locale->get_weekday_abbrev( $dateweekday );

                $datemeridiem = $datetime->format( 'a' );
                $datemeridiem = $wp_locale->get_meridiem( $datemeridiem );
                $datemeridiem_capital = $datetime->format( 'A' );
                $datemeridiem_capital = $wp_locale->get_meridiem( $datemeridiem_capital );

                $dateformatstring = ' '.$dateformatstring;
                $dateformatstring = preg_replace( "/([^\\\])D/", "\\1" . backslashit( $dateweekday_abbrev ), $dateformatstring );
                $dateformatstring = preg_replace( "/([^\\\])F/", "\\1" . backslashit( $datemonth ), $dateformatstring );
                $dateformatstring = preg_replace( "/([^\\\])l/", "\\1" . backslashit( $dateweekday ), $dateformatstring );
                $dateformatstring = preg_replace( "/([^\\\])M/", "\\1" . backslashit( $datemonth_abbrev ), $dateformatstring );
                $dateformatstring = preg_replace( "/([^\\\])a/", "\\1" . backslashit( $datemeridiem ), $dateformatstring );
                $dateformatstring = preg_replace( "/([^\\\])A/", "\\1" . backslashit( $datemeridiem_capital ), $dateformatstring );

                $dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
            }
            $timezone_formats = array( 'P', 'I', 'O', 'T', 'Z', 'e' );
            $timezone_formats_re = implode( '|', $timezone_formats );
            if ( preg_match( "/$timezone_formats_re/", $dateformatstring ) ) {
                $timezone_object = $ns->get_wp_timezone();
                $date_object = date_create( null, $timezone_object );
                foreach ( $timezone_formats as $timezone_format ) {
                    if ( false !== strpos( $dateformatstring, $timezone_format ) ) {
                        $formatted = date_format( $date_object, $timezone_format );
                        $dateformatstring = ' '.$dateformatstring;
                        $dateformatstring = preg_replace( "/([^\\\])$timezone_format/", "\\1" . backslashit( $formatted ), $dateformatstring );
                        $dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
                    }
                }
            }
            return $datetime->format( $dateformatstring );
        } );

    birch_defn( $ns, 'get_wp_datetime', function ( $arg ) use ( $ns ) {
            $timezone = $ns->get_wp_timezone();
            if ( is_array( $arg ) ) {
                $datetime = $ns->to_mysql_date( $arg );
                $datetime = new DateTime( $datetime, $timezone );
                return $datetime;
            }
            if ( (string) (int) $arg == $arg && (int) $arg > 0 ) {
                $datetime = new DateTime( '@' . $arg );
                $datetime->setTimezone( $timezone );
                return $datetime;
            }
            $datetime = new DateTime( $arg, $timezone );
            return $datetime;
        } );

    birch_defn( $ns, 'to_mysql_date', function ( $arg ) {
            $date = $arg['date'];
            $date = explode( '/', $date );
            $date = $date[2] . '-' . $date[0] . '-' . $date[1];
            $time = $arg['time'];
            $hours = floor( $time / 60 );
            $minutes = $time % 60;
            $date .= ' ' . $hours . ':' . $minutes . ':00';
            return $date;
        } );

    birch_defn( $ns, 'wp_format_time', function( $datetime ) use ( $ns ) {
            $time_format = get_option( 'time_format' );
            return $datetime->format( $time_format );
        } );

    birch_defn( $ns, 'wp_format_date', function( $datetime ) use ( $ns ) {
            $date_format = get_option( 'date_format' );
            $timestamp = $datetime->format( 'U' );
            return $ns->date_i18n( $date_format, $timestamp );
        } );

    birch_defn( $ns, 'convert_to_datetime', function ( $timestamp ) use ( $ns ) {
            $date_format = get_option( 'date_format' );
            $time_format = get_option( 'time_format' );
            $datetime = $ns->get_wp_datetime( $timestamp );
            $datetime_separator = $ns->get_datetime_separator();
            return $ns->date_i18n( $date_format, $timestamp ) . $datetime_separator .
            $datetime->format( $time_format );
        } );

    birch_defn( $ns, 'get_datetime_separator', function () use ( $ns ) {
            return ' ';
        } );

    birch_defn( $ns, 'get_wp_timezone', function () use ( $ns ) {
            $timezone = get_option( 'timezone_string' );
            $offset = get_option( 'gmt_offset' );
            if ( $timezone ) {
                $timezone = new DateTimeZone( $timezone );
            } else if ( $offset ) {
                $offset = -round( $offset );
                if ( $offset > 0 ) {
                    $offset = '+' . $offset;
                }
                $timezone = new DateTimeZone( 'Etc/GMT' . $offset );
            } else {
                $timezone = date_default_timezone_get();
                $timezone = new DateTimeZone( $timezone );
            }
            return $timezone;
        } );

    birch_defn( $ns, 'starts_with', function ( $haystack, $needle ) {
            return !strncmp( $haystack, $needle, strlen( $needle ) );
        } );

    birch_defn( $ns, 'ends_with', function ( $haystack, $needle ) {
            $length = strlen( $needle );
            if ( $length == 0 ) {
                return true;
            }

            return substr( $haystack, -$length ) === $needle;
        } );

}

birchbase_util_def();
