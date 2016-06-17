/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function (
        $,
        ko,
        Component,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        checkoutData,
        additionalValidators
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Ilio_Monetico/payment/monetico'
            },
            /**
             * Get value of instruction field.
             * @returns {String}
             */
            getInstructions: function () {
                // console.log("try to find instructions:"+this.item.method);
                // return window.checkoutConfig.payment.instructions[this.item.method];
            },
            afterPlaceOrder: function () {
                $.mage.redirect('/monetico/checkout/redirect');
            }
        });
    }
);
