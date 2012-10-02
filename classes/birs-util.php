<?php

class BIRS_Util {

    private static $instance;

    private function __construct() {
        
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new BIRS_Util();
        }
        return self::$instance;
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

    function get_time_options($interval = 15) {
        $options = array();
        $value = 0;
        $format1 = '%d:%02d AM';
        $format2 = '%d:%02d PM';
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
                $options[$value] = sprintf($format, $hour, $min);
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

    function render_html_options($options, $selection, $default = false) {
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
