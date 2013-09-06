<?php

class BIRS_Util {

    private static $instance;
    var $date_time_format_php_pattern;

    private function __construct() {
        $this->date_time_format_php_pattern = array(
            //day of month
            'd', //Numeric, with leading zeros
            'j', //Numeric, without leading zeros
            
            //weekday
            'l', //full name of the day
            'D', //Three letter name
            
            //month
            'F', //Month name full
            'M', //Month name short
            'n', //numeric month no leading zeros
            'm', //numeric month leading zeros
            
            //year
            'Y', //full numeric year
            'y', //numeric year: 2 digit
            
            //time
            'a',
            'A',
            'g', //Hour, 12-hour, without leading zeros
            'h', //Hour, 12-hour, with leading zeros
            'G', //Hour, 24-hour, without leading zeros
            'H', //Hour, 24-hour, with leading zeros
            'i' //Minutes, with leading zeros
        );

    }

    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new BIRS_Util();
        }
        return self::$instance;
    }

    function date_time_format_php_to_jquery($date_time_format) {
        $pattern = $this->date_time_format_php_pattern;
        $replace = array(
            'dd','d',
            'DD','D',
            'MM','M','m','mm',
            'yy','y',
            'am', 'AM', '', '', '', '', ''
        );
        foreach($pattern as &$p) {
            $p = '/'.$p.'/';
        }
        return preg_replace($pattern, $replace, $date_time_format); 
    }
    
    function date_time_format_php_to_fullcalendar($date_time_format) {
        $pattern = $this->date_time_format_php_pattern;
        $replace = array(
            'dd','d',
            'dddd','ddd',
            'MMMM','MMM','M','MM',
            'yyyy','yy',
            'tt', 'TT', 'h', 'hh', 'H', 'HH', 'mm'
        );
        foreach($pattern as &$p) {
            $p = '/'.$p.'/';
        }
        return preg_replace($pattern, $replace, $date_time_format); 
    }

    public function convert_to_datetime($timestamp) {
        $date_format = get_option( 'date_format' );
        $time_format = get_option( 'time_format' );
        $datetime = $this->get_wp_datetime($timestamp);
        $datetime_separator = apply_filters('birchschedule_datetime_separator', ' ');
        return $this->date_i18n($date_format, $timestamp) . $datetime_separator
             . $datetime->format($time_format);
    }

    function get_wp_timezone() {
        $timezone = get_option('timezone_string');
        $offset = get_option('gmt_offset');
        if ($timezone) {
            $timezone = new DateTimeZone($timezone);
        } else if ($offset) {
            $offset = -round($offset);
            if ($offset > 0) {
                $offset = '+' . $offset;
            }
            $timezone = new DateTimeZone('Etc/GMT' . $offset);
        } else {
            $timezone = date_default_timezone_get();
            $timezone = new DateTimeZone($timezone);
        }
        return $timezone;
    }

    function date_i18n($dateformatstring, $unixtimestamp) {
        global $wp_locale;
        $datetime = $this->get_wp_datetime($unixtimestamp);
        if ( ( !empty( $wp_locale->month ) ) && ( !empty( $wp_locale->weekday ) ) ) {
            $datemonth = $datetime->format('m');
            $datemonth = $wp_locale->get_month($datemonth);
            $datemonth_abbrev = $wp_locale->get_month_abbrev( $datemonth );

            $dateweekday = $datetime->format('w');
            $dateweekday = $wp_locale->get_weekday($dateweekday);
            $dateweekday_abbrev = $wp_locale->get_weekday_abbrev( $dateweekday );

            $datemeridiem = $datetime->format('a');
            $datemeridiem = $wp_locale->get_meridiem($datemeridiem);
            $datemeridiem_capital = $datetime->format('A');
            $datemeridiem_capital = $wp_locale->get_meridiem($datemeridiem_capital);

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
            $timezone_object = $this->get_wp_timezone();
            $date_object = date_create( null, $timezone_object );
            foreach( $timezone_formats as $timezone_format ) {
                if ( false !== strpos( $dateformatstring, $timezone_format ) ) {
                    $formatted = date_format( $date_object, $timezone_format );
                    $dateformatstring = ' '.$dateformatstring;
                    $dateformatstring = preg_replace( "/([^\\\])$timezone_format/", "\\1" . backslashit( $formatted ), $dateformatstring );
                    $dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
                }
            }
        }
        return $datetime->format($dateformatstring);
    }

    function get_day_minutes($datetime) {
        $time = $datetime->format('H') * 60 + $datetime->format('i');
        return $time;
    }

    function has_shortcode($shortcode = NULL) {

        $post_to_check = get_post(get_the_ID());

        // false because we have to search through the post content first
        $found = false;

        // if no short code was provided, return false
        if (!$shortcode) {
            return $found;
        }
        // check the post content for the short code
        if (stripos($post_to_check->post_content, '[' . $shortcode) !== FALSE && stripos($post_to_check->post_content, '[[' . $shortcode) == FALSE) {
            // we have found the short code
            $found = TRUE;
        }

        // return our final results
        return $found;
    }

    function to_mysql_date($arg) {
        $date = $arg['date'];
        $date = explode('/', $date);
        $date = $date[2] . '-' . $date[0] . '-' . $date[1];
        $time = $arg['time'];
        $hours = floor($time / 60);
        $minutes = $time % 60;
        $date .= ' ' . $hours . ':' . $minutes . ':00';
        return $date;
    }

    function get_wp_datetime($arg) {
        $timezone = $this->get_wp_timezone();
        if (is_array($arg)) {
            $datetime = $this->to_mysql_date($arg);
            $datetime = new DateTime($datetime, $timezone);
            return $datetime;
        }
        if ((string) (int) $arg == $arg && (int) $arg > 0) {
            $datetime = new DateTime('@' . $arg);
            $datetime->setTimezone($timezone);
            return $datetime;
        }
        $datetime = new DateTime($arg, $timezone);
        return $datetime;
    }
    
    function get_weekdays_short() {
        return array(
            __('Sun', 'birchschedule'),
            __('Mon', 'birchschedule'),
            __('Tue', 'birchschedule'),
            __('Wed', 'birchschedule'),
            __('Thu', 'birchschedule'),
            __('Fri', 'birchschedule'),
            __('Sat', 'birchschedule')
        );
    }

    function get_calendar_views() {
        return array(
            'month' => __('Month', 'birchschedule'),
            'agendaWeek' => __('Week', 'birchschedule'),
            'agendaDay' => __('Day', 'birchschedule')
        );
    }

    function get_countries() {
        return array(
            "AF" => "Afghanistan",
            "AL" => "Albania",
            "DZ" => "Algeria",
            "AS" => "American Samoa",
            "AD" => "Andorra",
            "AO" => "Angola",
            "AI" => "Anguilla",
            "AQ" => "Antarctica",
            "AG" => "Antigua And Barbuda",
            "AR" => "Argentina",
            "AM" => "Armenia",
            "AW" => "Aruba",
            "AU" => "Australia",
            "AT" => "Austria",
            "AZ" => "Azerbaijan",
            "BS" => "Bahamas",
            "BH" => "Bahrain",
            "BD" => "Bangladesh",
            "BB" => "Barbados",
            "BY" => "Belarus",
            "BE" => "Belgium",
            "BZ" => "Belize",
            "BJ" => "Benin",
            "BM" => "Bermuda",
            "BT" => "Bhutan",
            "BO" => "Bolivia",
            "BA" => "Bosnia And Herzegowina",
            "BW" => "Botswana",
            "BV" => "Bouvet Island",
            "BR" => "Brazil",
            "IO" => "British Indian Ocean Territory",
            "BN" => "Brunei Darussalam",
            "BG" => "Bulgaria",
            "BF" => "Burkina Faso",
            "BI" => "Burundi",
            "KH" => "Cambodia",
            "CM" => "Cameroon",
            "CA" => "Canada",
            "CV" => "Cape Verde",
            "KY" => "Cayman Islands",
            "CF" => "Central African Republic",
            "TD" => "Chad",
            "CL" => "Chile",
            "CN" => "China",
            "CX" => "Christmas Island",
            "CC" => "Cocos (Keeling) Islands",
            "CO" => "Colombia",
            "KM" => "Comoros",
            "CG" => "Congo",
            "CD" => "Congo, The Democratic Republic Of The",
            "CK" => "Cook Islands",
            "CR" => "Costa Rica",
            "CI" => "Cote D'Ivoire",
            "HR" => "Croatia (Local Name: Hrvatska)",
            "CU" => "Cuba",
            "CY" => "Cyprus",
            "CZ" => "Czech Republic",
            "DK" => "Denmark",
            "DJ" => "Djibouti",
            "DM" => "Dominica",
            "DO" => "Dominican Republic",
            "TP" => "East Timor",
            "EC" => "Ecuador",
            "EG" => "Egypt",
            "SV" => "El Salvador",
            "GQ" => "Equatorial Guinea",
            "ER" => "Eritrea",
            "EE" => "Estonia",
            "ET" => "Ethiopia",
            "FK" => "Falkland Islands (Malvinas)",
            "FO" => "Faroe Islands",
            "FJ" => "Fiji",
            "FI" => "Finland",
            "FR" => "France",
            "FX" => "France, Metropolitan",
            "GF" => "French Guiana",
            "PF" => "French Polynesia",
            "TF" => "French Southern Territories",
            "GA" => "Gabon",
            "GM" => "Gambia",
            "GE" => "Georgia",
            "DE" => "Germany",
            "GH" => "Ghana",
            "GI" => "Gibraltar",
            "GR" => "Greece",
            "GL" => "Greenland",
            "GD" => "Grenada",
            "GP" => "Guadeloupe",
            "GU" => "Guam",
            "GT" => "Guatemala",
            "GN" => "Guinea",
            "GW" => "Guinea-Bissau",
            "GY" => "Guyana",
            "HT" => "Haiti",
            "HM" => "Heard And Mc Donald Islands",
            "VA" => "Holy See (Vatican City State)",
            "HN" => "Honduras",
            "HK" => "Hong Kong",
            "HU" => "Hungary",
            "IS" => "Iceland",
            "IN" => "India",
            "ID" => "Indonesia",
            "IR" => "Iran (Islamic Republic Of)",
            "IQ" => "Iraq",
            "IE" => "Ireland",
            "IL" => "Israel",
            "IT" => "Italy",
            "JM" => "Jamaica",
            "JP" => "Japan",
            "JO" => "Jordan",
            "KZ" => "Kazakhstan",
            "KE" => "Kenya",
            "KI" => "Kiribati",
            "KP" => "Korea, Democratic People's Republic Of",
            "KR" => "Korea, Republic Of",
            "KW" => "Kuwait",
            "KG" => "Kyrgyzstan",
            "LA" => "Lao People's Democratic Republic",
            "LV" => "Latvia",
            "LB" => "Lebanon",
            "LS" => "Lesotho",
            "LR" => "Liberia",
            "LY" => "Libyan Arab Jamahiriya",
            "LI" => "Liechtenstein",
            "LT" => "Lithuania",
            "LU" => "Luxembourg",
            "MO" => "Macau",
            "MK" => "Macedonia, Former Yugoslav Republic Of",
            "MG" => "Madagascar",
            "MW" => "Malawi",
            "MY" => "Malaysia",
            "MV" => "Maldives",
            "ML" => "Mali",
            "MT" => "Malta",
            "MH" => "Marshall Islands",
            "MQ" => "Martinique",
            "MR" => "Mauritania",
            "MU" => "Mauritius",
            "YT" => "Mayotte",
            "MX" => "Mexico",
            "FM" => "Micronesia, Federated States Of",
            "MD" => "Moldova, Republic Of",
            "MC" => "Monaco",
            "MN" => "Mongolia",
            "MS" => "Montserrat",
            "MA" => "Morocco",
            "MZ" => "Mozambique",
            "MM" => "Myanmar",
            "NA" => "Namibia",
            "NR" => "Nauru",
            "NP" => "Nepal",
            "NL" => "Netherlands",
            "AN" => "Netherlands Antilles",
            "NC" => "New Caledonia",
            "NZ" => "New Zealand",
            "NI" => "Nicaragua",
            "NE" => "Niger",
            "NG" => "Nigeria",
            "NU" => "Niue",
            "NF" => "Norfolk Island",
            "MP" => "Northern Mariana Islands",
            "NO" => "Norway",
            "OM" => "Oman",
            "PK" => "Pakistan",
            "PW" => "Palau",
            "PA" => "Panama",
            "PG" => "Papua New Guinea",
            "PY" => "Paraguay",
            "PE" => "Peru",
            "PH" => "Philippines",
            "PN" => "Pitcairn",
            "PL" => "Poland",
            "PT" => "Portugal",
            "PR" => "Puerto Rico",
            "QA" => "Qatar",
            "RE" => "Reunion",
            "RO" => "Romania",
            "RU" => "Russian Federation",
            "RW" => "Rwanda",
            "KN" => "Saint Kitts And Nevis",
            "LC" => "Saint Lucia",
            "VC" => "Saint Vincent And The Grenadines",
            "WS" => "Samoa",
            "SM" => "San Marino",
            "ST" => "Sao Tome And Principe",
            "SA" => "Saudi Arabia",
            "SN" => "Senegal",
            "SC" => "Seychelles",
            "SL" => "Sierra Leone",
            "SG" => "Singapore",
            "SK" => "Slovakia (Slovak Republic)",
            "SI" => "Slovenia",
            "SB" => "Solomon Islands",
            "SO" => "Somalia",
            "ZA" => "South Africa",
            "GS" => "South Georgia, South Sandwich Islands",
            "ES" => "Spain",
            "LK" => "Sri Lanka",
            "SH" => "St. Helena",
            "PM" => "St. Pierre And Miquelon",
            "SD" => "Sudan",
            "SR" => "Suriname",
            "SJ" => "Svalbard And Jan Mayen Islands",
            "SZ" => "Swaziland",
            "SE" => "Sweden",
            "CH" => "Switzerland",
            "SY" => "Syrian Arab Republic",
            "TW" => "Taiwan",
            "TJ" => "Tajikistan",
            "TZ" => "Tanzania, United Republic Of",
            "TH" => "Thailand",
            "TG" => "Togo",
            "TK" => "Tokelau",
            "TO" => "Tonga",
            "TT" => "Trinidad And Tobago",
            "TN" => "Tunisia",
            "TR" => "Turkey",
            "TM" => "Turkmenistan",
            "TC" => "Turks And Caicos Islands",
            "TV" => "Tuvalu",
            "UG" => "Uganda",
            "UA" => "Ukraine",
            "AE" => "United Arab Emirates",
            "GB" => "United Kingdom",
            "US" => "United States",
            "UM" => "United States Minor Outlying Islands",
            "UY" => "Uruguay",
            "UZ" => "Uzbekistan",
            "VU" => "Vanuatu",
            "VE" => "Venezuela",
            "VN" => "Viet Nam",
            "VG" => "Virgin Islands (British)",
            "VI" => "Virgin Islands (U.S.)",
            "WF" => "Wallis And Futuna Islands",
            "EH" => "Western Sahara",
            "YE" => "Yemen",
            "YU" => "Yugoslavia",
            "ZM" => "Zambia",
            "ZW" => "Zimbabwe"
        );
    }

    function get_us_states() {
        return array(
            'AL' => 'Alabama (AL)',
            'AK' => 'Alaska (AK)',
            'AZ' => 'Arizona (AZ)',
            'AR' => 'Arkansas (AR)',
            'CA' => 'California (CA)',
            'CO' => 'Colorado (CO)',
            'CT' => 'Conneticut (CT)',
            'DC' => 'District of Columbia (DC)',
            'DE' => 'Delaware (DE)',
            'FL' => 'Florida (FL)',
            'GA' => 'Georgia (GA)',
            'HI' => 'Hawaii (HI)',
            'ID' => 'Idaho (ID)',
            'IL' => 'Illinois (IL)',
            'IN' => 'Indiana (IN)',
            'IA' => 'Iowa (IA)',
            'KS' => 'Kansas (KS)',
            'KY' => 'Kentucky (KY)',
            'LA' => 'Louisiana (LA)',
            'ME' => 'Maine (ME)',
            'MD' => 'Maryland (MD)',
            'MA' => 'Massachusetts (MA)',
            'MI' => 'Michigan (MI)',
            'MN' => 'Minnesota (MN)',
            'MS' => 'Mississippi (MS)',
            'MO' => 'Missouri (MO)',
            'MT' => 'Montana (MT)',
            'NE' => 'Nebraska (NE)',
            'NV' => 'Nevada (NV)',
            'NH' => 'New Hampshire (NH)',
            'NJ' => 'New Jersey (NJ)',
            'NM' => 'New Mexico (NM)',
            'NY' => 'New York (NY)',
            'NC' => 'North Carolina(NC)',
            'ND' => 'North Dakota (ND)',
            'OH' => 'Ohio (OH)',
            'OK' => 'Oklahoma (OK)',
            'OR' => 'Oregon (OR)',
            'PA' => 'Pennsylvania (PA)',
            'PR' => 'Puerto Rico (PR)',
            'RI' => 'Rhode Island (RI)',
            'SC' => 'South Carolina (SC)',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee (TN)',
            'TX' => 'Texas (TX)',
            'UT' => 'Utah (UT)',
            'VA' => 'Virginia (VA)',
            'VI' => 'Virgin Islands (VI)',
            'VT' => 'Vermont (VT)',
            'WA' => 'Washington (WA)',
            'WV' => 'West Virginia (WV)',
            'WI' => 'Wisconsin (WI)',
            'WY' => 'Wyoming (WY)'
        );
    }

    function get_currencies() {
        return array(
            'USD' => array('title' => __('U.S. Dollar', 'birchschedule'), 'code' => 'USD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'EUR' => array('title' => __('Euro', 'birchschedule'), 'code' => 'EUR', 'symbol_left' => '', 'symbol_right' => '€', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'GBP' => array('title' => __('Pounds Sterling', 'birchschedule'), 'code' => 'GBP', 'symbol_left' => '£', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'AUD' => array('title' => __('Australian Dollar', 'birchschedule'), 'code' => 'AUD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'BRL' => array('title' => __('Brazilian Real', 'birchschedule'), 'code' => 'BRL', 'symbol_left' => 'R$', 'symbol_right' => '', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
            'CAD' => array('title' => __('Canadian Dollar', 'birchschedule'), 'code' => 'CAD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'CNY' => array('title' => __('Chinese RMB', 'birchschedule'), 'code' => 'CNY', 'symbol_left' => '￥', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'CZK' => array('title' => __('Czech Koruna', 'birchschedule'), 'code' => 'CZK', 'symbol_left' => '', 'symbol_right' => 'Kč', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
            'DKK' => array('title' => __('Danish Krone', 'birchschedule'), 'code' => 'DKK', 'symbol_left' => '', 'symbol_right' => 'kr', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
            'HKD' => array('title' => __('Hong Kong Dollar', 'birchschedule'), 'code' => 'HKD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'HUF' => array('title' => __('Hungarian Forint', 'birchschedule'), 'code' => 'HUF', 'symbol_left' => '', 'symbol_right' => 'Ft', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'INR' => array('title' => __('Indian Rupee', 'birchschedule'), 'code' => 'INR', 'symbol_left' => 'Rs.', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'ILS' => array('title' => __('Israeli New Shekel', 'birchschedule'), 'code' => 'ILS', 'symbol_left' => '₪', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'JPY' => array('title' => __('Japanese Yen', 'birchschedule'), 'code' => 'JPY', 'symbol_left' => '¥', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'MYR' => array('title' => __('Malaysian Ringgit', 'birchschedule'), 'code' => 'MYR', 'symbol_left' => 'RM', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'MXN' => array('title' => __('Mexican Peso', 'birchschedule'), 'code' => 'MXN', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'NZD' => array('title' => __('New Zealand Dollar', 'birchschedule'), 'code' => 'NZD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'NOK' => array('title' => __('Norwegian Krone', 'birchschedule'), 'code' => 'NOK', 'symbol_left' => 'kr', 'symbol_right' => '', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
            'PHP' => array('title' => __('Philippine Peso', 'birchschedule'), 'code' => 'PHP', 'symbol_left' => 'Php', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'PLN' => array('title' => __('Polish Zloty', 'birchschedule'), 'code' => 'PLN', 'symbol_left' => '', 'symbol_right' => 'zł', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
            'SGD' => array('title' => __('Singapore Dollar', 'birchschedule'), 'code' => 'SGD', 'symbol_left' => '$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'ZAR' => array('title' => __('South Africa Rand', 'birchschedule'), 'code' => 'ZAR', 'symbol_left' => 'R', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'SEK' => array('title' => __('Swedish Krona', 'birchschedule'), 'code' => 'SEK', 'symbol_left' => '', 'symbol_right' => 'kr', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
            'CHF' => array('title' => __('Swiss Franc', 'birchschedule'), 'code' => 'CHF', 'symbol_left' => '', 'symbol_right' => 'CHF', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
            'TWD' => array('title' => __('Taiwan New Dollar', 'birchschedule'), 'code' => 'TWD', 'symbol_left' => 'NT$', 'symbol_right' => '', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'THB' => array('title' => __('Thai Baht', 'birchschedule'), 'code' => 'THB', 'symbol_left' => '', 'symbol_right' => '฿', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2'),
            'TRY' => array('title' => __('Turkish Lira', 'birchschedule'), 'code' => 'TRY', 'symbol_left' => '', 'symbol_right' => 'TL', 'decimal_point' => ',', 'thousands_point' => '.', 'decimal_places' => '2'),
            'AED' => array('title' => __('United Arab Emirates Dirham', 'birchschedule'), 'code' => 'AED', 'symbol_left' => '', 'symbol_right' => 'AED', 'decimal_point' => '.', 'thousands_point' => ',', 'decimal_places' => '2')
        );
    }
    
    function convert_mins_to_time_option($mins) {
        $hour = $mins / 60;
        $min = $mins % 60;
        $date_sample = '2013-01-01 %02d:%02d:00';
        $timezone = $this->get_wp_timezone();
        $datetime = new DateTime(sprintf($date_sample, $hour, $min), $timezone);
        $option_text = $datetime->format(get_option('time_format'));    
        return $option_text;
    }

    function get_time_options($interval = 15) {
        $options = array();
        $value = 0;
        $format1 = '%d:%02d AM';
        $format2 = '%d:%02d PM';
        $date_sample = '2013-01-01 %02d:%02d:00';
        for ($i = 0; $i < 24; $i++) {
            if ($i === 0) {
                $hour = 12;
                $format = $format1;
            } else if ($i === 12) {
                $hour = 12;
                $format = $format2;
            } else if ($i < 12) {
                $hour = $i;
                $format = $format1;
            } else if ($i > 12) {
                $hour = $i - 12;
                $format = $format2;
            }
            for ($min = 0; $min < 60; $min += $interval) {
                $timezone = $this->get_wp_timezone();
                $datetime = new DateTime(sprintf($date_sample, $i, $min), $timezone);
                $option_text = $datetime->format(get_option('time_format'));
                $options[$value] = $option_text;
                $value += $interval;
            }
        }
        return $options;
    }

    function get_client_title_options() {
        return array('Mr' => __('Mr', 'birchschedule'),
            'Mrs' => __('Mrs', 'birchschedule'),
            'Miss' => __('Miss', 'birchschedule'),
            'Ms' => __('Ms', 'birchschedule'),
            'Dr' => __('Dr', 'birchschedule'));
    }
    
    function get_gmt_offset() {
        return -round($this->get_wp_datetime(time())->getOffset() / 60);
    }

    function render_html_options($options, $selection = false, $default = false) {
        if ($selection == false && $default != false) {
            $selection = $default;
        }
        foreach ($options as $val => $text) {
            if ($selection == $val) {
                $selected = ' selected="selected" ';
            } else {
                $selected = '';
            }
            echo "<option value='$val' $selected>$text</option>";
        }
    }
    
    function get_fullcalendar_i18n_params() {
        return array(
            'firstDay' => apply_filters('birchschedule_first_day_of_week', 0),
            'monthNames'=> array(
                __('January', 'birchschedule'),
                __('February', 'birchschedule'), 
                __('March', 'birchschedule'),
                __('April', 'birchschedule'), 
                __('May', 'birchschedule'), 
                __('June', 'birchschedule'),
                __('July', 'birchschedule'),
                __('August', 'birchschedule'),
                __('September', 'birchschedule'),
                __('October', 'birchschedule'),
                __('November', 'birchschedule'),
                __('December', 'birchschedule')
            ),
            'monthNamesShort'=> array(
                __('Jan', 'birchschedule'),
                __('Feb', 'birchschedule'), 
                __('Mar', 'birchschedule'),
                __('Apr', 'birchschedule'), 
                __('May', 'birchschedule'), 
                __('Jun', 'birchschedule'),
                __('Jul', 'birchschedule'),
                __('Aug', 'birchschedule'),
                __('Sep', 'birchschedule'),
                __('Oct', 'birchschedule'),
                __('Nov', 'birchschedule'),
                __('Dec', 'birchschedule')
            ),
            'dayNames'=> array(
                __('Sunday', 'birchschedule'),
                __('Monday', 'birchschedule'), 
                __('Tuesday', 'birchschedule'),
                __('Wednesday', 'birchschedule'), 
                __('Thursday', 'birchschedule'), 
                __('Friday', 'birchschedule'),
                __('Saturday', 'birchschedule')
            ),
            'dayNamesShort'=> array(
                __('Sun', 'birchschedule'),
                __('Mon', 'birchschedule'), 
                __('Tue', 'birchschedule'),
                __('Wed', 'birchschedule'), 
                __('Thu', 'birchschedule'), 
                __('Fri', 'birchschedule'),
                __('Sat', 'birchschedule')
            ),
            'buttonText' => array(
                'today' => __('today', 'birchschedule'),
                'month' => __('month', 'birchschedule'),
                'week' => __('week', 'birchschedule'),
                'day' => __('day', 'birchschedule')
            )
        );
    }
    
    function get_datepicker_i18n_params() {
        return array(
            'firstDay' => apply_filters('birchschedule_first_day_of_week', 0),
            'monthNames'=> array(
                __('January', 'birchschedule'),
                __('February', 'birchschedule'), 
                __('March', 'birchschedule'),
                __('April', 'birchschedule'), 
                __('May', 'birchschedule'), 
                __('June', 'birchschedule'),
                __('July', 'birchschedule'),
                __('August', 'birchschedule'),
                __('September', 'birchschedule'),
                __('October', 'birchschedule'),
                __('November', 'birchschedule'),
                __('December', 'birchschedule')
            ),
            'monthNamesShort'=> array(
                __('Jan', 'birchschedule'),
                __('Feb', 'birchschedule'), 
                __('Mar', 'birchschedule'),
                __('Apr', 'birchschedule'), 
                __('May', 'birchschedule'), 
                __('Jun', 'birchschedule'),
                __('Jul', 'birchschedule'),
                __('Aug', 'birchschedule'),
                __('Sep', 'birchschedule'),
                __('Oct', 'birchschedule'),
                __('Nov', 'birchschedule'),
                __('Dec', 'birchschedule')
            ),
            'dayNames'=> array(
                __('Sunday', 'birchschedule'),
                __('Monday', 'birchschedule'), 
                __('Tuesday', 'birchschedule'),
                __('Wednesday', 'birchschedule'), 
                __('Thursday', 'birchschedule'), 
                __('Friday', 'birchschedule'),
                __('Saturday', 'birchschedule')
            ),
            'dayNamesShort'=> array(
                __('Sun', 'birchschedule'),
                __('Mon', 'birchschedule'), 
                __('Tue', 'birchschedule'),
                __('Wed', 'birchschedule'), 
                __('Thu', 'birchschedule'), 
                __('Fri', 'birchschedule'),
                __('Sat', 'birchschedule')
            ),
            'dayNamesMin'=> array(
                __('Su', 'birchschedule'),
                __('Mo', 'birchschedule'), 
                __('Tu', 'birchschedule'),
                __('We', 'birchschedule'), 
                __('Th', 'birchschedule'), 
                __('Fr', 'birchschedule'),
                __('Sa', 'birchschedule')
            )
        );
    }
    
    function starts_with($haystack, $needle)
    {
        return !strncmp($haystack, $needle, strlen($needle));
    }
    
    function ends_with($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }
    
        return (substr($haystack, -$length) === $needle);
    }

    function current_page_url() {
         $pageURL = 'http';
         if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on"){
         	$pageURL .= "s";
         }
         $pageURL .= "://";
         if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
         } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
         }
         return $pageURL;
    }

    public function log() {
        if (WP_DEBUG === true) {
            $args = func_get_args();
            $message = '';
            foreach ($args as $arg) {
                if (is_array($arg) || is_object($arg)) {
                    $message .= print_r($arg, true);
                } else {
                    $message .= $arg;
                }
            }
            error_log($message);
        }
    }

}

?>
