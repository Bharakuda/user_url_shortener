<?php

namespace UserBundle\Service;


class TokenGenerator
{
    // generate token with $length/2 number of characters
    public function generateToken()
    {
        $length = 16;
        $token = bin2hex(random_bytes($length));
        return $token;
    }
}
