<?php

namespace App\Classes;

class Networking
{
    /**
     * Validate an input as an IP address
     *
     * @param  string $input
     * @param  bool   $allow_cidr (false) - Whether or not CIDR notation should be supported, e.g. 127.0.0.1/32
     * @return bool
     */
    public function validateIp($input, $allow_cidr = FALSE)
    {
        $is_traditional_ip = filter_var($input, FILTER_VALIDATE_IP);

        if (! $allow_cidr || $is_traditional_ip) {
            return $is_traditional_ip;
        }

        return $this->validateCidrNotation($input);
    }

    /**
     * Validate an input as a CIDR formatted string
     *
     * @param  string $input
     * @return bool
     */
    public function validateCidrNotation($input)
    {
        return (bool) preg_match(
            '/^((\b|\.)(0|1|2(?!5(?=6|7|8|9)|6|7|8|9))?\d{1,2}){4}(\/(((?!00)(0|1|2|3(?=0|1|2))\d|\d)))?$/',
            $input
        );
    }
}