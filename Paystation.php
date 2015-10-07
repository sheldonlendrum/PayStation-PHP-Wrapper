<?php
    /**
     * Paystation PHP Wrapper
     *
     * @author Sheldon Lendrum
     **/
    class Paystation {

        var $test_mode      = PAYSTATION_TEST_MODE;
        var $account_id;
        var $gateway_id;
        var $hamc_auth_key;

        var $paystation_url = 'https://www.paystation.co.nz/direct/paystation.dll';
        var $return_url     = FALSE; // 'http://127.0.0.1/index.php?a=return';
        var $post_url       = FALSE; //'http://127/0/0/1/index.php?a=post';

        function __construct() {

        }

        function setAccountId($account_id = '') {

            if($account_id) $this->account_id = $account_id;

        }
        function getAccountId() {

            return $this->account_id;

        }

        function setGatewayId($gateway_id = '') {

            if($gateway_id) $this->gateway_id = $gateway_id;

        }

        function getGatewayId() {

            return $this->gateway_id;

        }

        function setReturnUrl($return_url = FALSE) {

            $this->return_url = $return_url;

        }

        function getReturnUrl() {

            return $this->return_url;

        }

        function setHMACKey($hamc_auth_key = FALSE) {

            $this->hamc_auth_key = $hamc_auth_key;

        }

        function getHMACKey() {

            return $this->hamc_auth_key;

        }

        function request($reference, $amount) {

            $params = array(
                'pstn_mr'    => $reference,
                'pstn_am'    => ($amount * 100)
            );
            $result = $this->_doRequest($params);
            return new SimpleXMLElement($result);

        }

        function chargeToken($reference, $amount, $token) {

            $params = array(
                'pstn_mr'    => $reference,
                'pstn_fp'    => 'T',
                'pstn_ft'    => $token,
                'pstn_am'    => ($amount * 100),
                'pstn_2p'    => 'T'
            );

            $result = $this->_doRequest($params);
            return new SimpleXMLElement($result);

        }

        function getToken($reference) {

            $params = array(
                'pstn_mr'    => $reference,
                'pstn_fp'    => 'T',
                // 'pstn_fs'    => 'T',
                'pstn_am'    => 100,
                'pstn_pa'    => 'T',
                'pstn_ft'    => NULL
            );
            $result = $this->_doRequest($params);
            return new SimpleXMLElement($result);

        }

        function _payStationToken() {
            $seed = (double)microtime()*getrandmax();
            srand($seed);
            $p = 0;
            $token = '';
            while ($p < 8) {
                $r = 123-(rand()%75);
                $token .= chr($r);
                $p++;
            }
            $token = preg_replace("/[^a-zA-NP-Z1-9+]/","", $token);
            if(strlen($token) < 8) {
                $token = $this->_payStationToken();
            }
            return urlencode(time().'-'. $token);
        }

        function _doRequest($params = array()) {

            $default_params = array(
                'paystation' => '_empty',
                'pstn_pi'    => $this->account_id,
                'pstn_gi'    => $this->gateway_id,
                'pstn_ms'    => $this->_payStationToken(),
                'pstn_nr'    => 't'
            );

            if($this->return_url) $default_params['pstn_du'] = $this->return_url;
            if($this->post_url) $default_params['pstn_dp'] = $this->post_url;


            $timetamp = time();
            if($this->test_mode) $params['pstn_tm'] ='t';

            $params = array_merge($params, $default_params);
            $payment_string = $this->buildUrl($params);

            if($this->getReturnUrl()) {
                $body = pack('a*', $timetamp).pack('a*', 'paystation').pack('a*', $payment_string);
                $hash = hash_hmac('sha512', $body, $this->hamc_auth_key);
                $this->paystation_url .= '?pstn_HMACTimestamp='. $timetamp;
                $this->paystation_url .= '&pstn_HMAC='. $hash;
            }

            return $this->_curlRequest($payment_string);

        }

        function _curlRequest($payment_string) {

            $curl_handler = curl_init($this->paystation_url);

            curl_setopt($curl_handler, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl_handler, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl_handler, CURLOPT_POST, TRUE);
            curl_setopt($curl_handler, CURLOPT_POSTFIELDS, $payment_string);
            curl_setopt($curl_handler, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($curl_handler, CURLOPT_HEADER, FALSE);
            curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, TRUE);
            $output = curl_exec($curl_handler);
            curl_close($curl_handler);
            return $output;

        }

        function buildUrl($params) {
            $post_string = '';
            foreach ($params as $key => $value) {
                $key = urlencode($key);
                $value = urlencode($value);
                $post_string .= $key.'='.trim($value).'&';
            }
            return rtrim($post_string, '&');
        }

    }