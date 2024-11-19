<?php

namespace Railsformers\Checkout\Helper;

class PaymentForShipping{

    public function getPayments()
    {
        $payments = [
            'freeshipping' => 'cashondelivery',
            'balik_do_ruky' => 'paypal_billing_agreement,comgate,free,cashondelivery',
            'balik_na_postu' => 'free',
            'ppl' => 'comgate,cashondelivery',
            'parcelshop' => 'comgate,cashondelivery',
            'flatrate' => 'comgate,checkmo',
            'dhl2' => 'comgate,cashondelivery'
        ];

        return $payments;
    }
}
