<?php

            // Example config settings.
            define('PAYSTATION_ACCOUNT_ID', 123456);
            define('PAYSTATION_GATEWAY_ID', 'PAYSTATION');

            // HMAC Key is NOT required.
            // Using a HAMC Key means you can used a dynamic retun URL point.
            define('PAYSTATION_HMAC_KEY', FALSE);

            // Set up the PayStation Connector.
            $paystation = new Paystation();
            $paystation->setAccountId(PAYSTATION_ACCOUNT_ID);
            $paystation->setGatewayId(PAYSTATION_GATEWAY_ID);

            if(PAYSTATION_HMAC_KEY) {
                $paystation->setHMACKey(PAYSTATION_HMAC_KEY);
                $paystation->setReturnUrl('http://www.mytestsite.com/process_payment.php');
            }





            // Example method to capture a card token.
            $payment_reference = 'my payment/account/order identifier';

            if($response = $paystation->getToken($payment_reference)) {

                if($response->DigitalOrder) {

                    header("Location: {$response->DigitalOrder}");
                    die('<a href="'. $response->DigitalOrder .'">continue</a>');

                } else {

                    // capture error
                    echo $response->em;

                }
            } else {

                // capture error
                echo $response->em;

            }





            // process_payment.php
            if(isset($_REQUEST['ms'])) {

                if($_REQUEST['ec'] == 0) {


                    $transation_data = $_REQUEST;

                    // store token, and other card details to database..
                    echo 'Success, token saved for card: '. $request[''];

                } else {

                    echo 'Your card was unable to be saved. Please try again. <br>Reason:  '. $_REQUEST['em'];

                }

            }




            // how to charge a card using a token.
            // Reference.
            $payment_reference = 'my payment/account/order identifier';

            // Amount.
            $amount = 99.95;

            // Future Pay Token ( from your database, or the paystation::getToken() request. )
            $token = $database_query->future_pay_token;

            if($response = $paystation->chargeToken($payment_reference, $amount, $token)) {
                if($response->ec == 0) {

                    echo 'Success: '. $response->em;


                } else {

                    echo 'Error: '. $response->em;

                }
            }
