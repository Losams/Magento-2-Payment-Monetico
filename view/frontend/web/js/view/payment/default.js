/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(

            {
                type: 'moneticopayment',
                component: 'Ilio_Monetico/js/view/payment/method-renderer/monetico-method'
            }

        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
