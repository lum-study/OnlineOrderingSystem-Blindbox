<?php
class Recaptcha {
    public static function verify($response = null, $remoteIp = null) {
        // reCAPTCHA disabled
        return true;
    }
}
