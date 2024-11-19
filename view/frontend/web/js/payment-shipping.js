require([
    'jquery'
], function ($) {
    'use strict';
    $(document).ready(function() {
        if($('.shipping-method-list input[type=radio]:checked').length == 0)
        {
            $('.payment-method-list input[type=radio]').prop('disabled', true);
        }

        $('.shipping-method-list input[type=radio]').change(function(){
            if($(this).is(':checked'))
            {
                var selectedShippingMethod = $(this).val();

                $('.payment-method-list input[type=radio]').prop('checked', false);

                $.ajax({
                    url: '/railsformers_checkout/checkout/selectshipping',
                    type: 'POST',
                    dataType: 'json',
                    data: {shipping_method: selectedShippingMethod},
                    success: function (data){

                        var content = $('.final-summary').html();
                        $('.final-summary').html(content);

                        $('.shipping-price').text(data.shipping_price);

                        $('.price-without-tax').text(data.price_without_tax);
                        $('.price-with-tax').html('<strong>'+data.price_with_tax+'</strong>')

                        if(data.payment_price)
                            $('.payment-price .price').text('+ ' + data.payment_price);

                        $('.payment-method-list input[type=radio]').each(function() {
                            var paymentName = $(this).val();
                            if (data.allowed_payments.includes(paymentName)) {
                                $(this).prop('disabled', false);
                                $('.payment-method-list').css('opacity', '1');
                                $(this).closest('li').css('opacity', '1');
                            }
                            else{
                                $(this).prop('disabled', true);
                                $(this).closest('li').css('opacity', '0.5');
                            }
                        });
                    }
                })
            }
        });

        $('.payment-method-list input[type=radio]').change(function (){

            var selectedPaymentMethod = $(this).val();

            var paymentPrice = '';
            if(selectedPaymentMethod == 'cashondelivery')
                paymentPrice = $('.payment-price .price').text().replace('+','');
            else
                paymentPrice = '0,00 ' + $('#currency').val();

            $.ajax({
                url: '/railsformers_checkout/checkout/selectpayment',
                type: 'POST',
                dataType: 'json',
                data: {payment_method: selectedPaymentMethod},
                success: function (data){
                    $('.summary-item .payment-price').text(paymentPrice);

                    $('.price-without-tax').text(data.price_without_tax);
                    $('.price-with-tax').html('<strong>'+data.price_with_tax+'</strong>')
                    var content = $('.final-summary').html();
                    $('.final-summary').html(content);
                }
            })
        });

        $('#select-parcelshop-address').click(function (){
            var link = document.createElement("link");
            link.rel = "stylesheet";
            link.href = "https://www.ppl.cz/sources/map/main.css";

            // Create a script element to load the main.js file
            var script = document.createElement("script");
            script.src = "https://www.ppl.cz/sources/map/main.js";

            // Add the script+href link to the document head
            document.head.appendChild(link);
            document.head.appendChild(script);
            $('.modal-overlay').show();
            $('.parcelshop-modal').css('visibility', 'visible');
            $('body').addClass('body-no-scroll');
        });

        $('.modal-overlay, .close-parcelshop-modal').click(function() {
            $('.modal-overlay').hide();
            $('.parcelshop-modal').css('visibility', 'hidden');
            $('body').removeClass('body-no-scroll');
        });

        // Přidání posluchače události pro 'ppl-parcelshop-map'
        document.addEventListener(
            "ppl-parcelshop-map",
            function(event) {


                $('.parcelshop-address').remove();

                var name = event.detail.name;
                var street = event.detail.street;
                var zipCode = event.detail.zipCode;
                var city = event.detail.city;
                var country = event.detail.country;

                var addressText = name + ', ' + street + ', ' + zipCode + ', ' + city;

                var newSpan = $('<div class="parcelshop-address">').text(addressText);

                var parcelshopData = {
                    id: event.detail.id,
                    code: event.detail.code,
                    dhlPsId: event.detail.dhlPsId,
                    name: name,
                    street: street,
                    zipCode: zipCode,
                    city: city,
                    country: country
                };

                console.log(parcelshopData);

                var parcelshopJson = JSON.stringify(parcelshopData);
                $('.parcelshop-pickup-point').val(parcelshopJson);
                $('#select-parcelshop-address').before(newSpan);

                console.log("Vybraný parcel shop:", event.detail);
                $('.parcelshop-modal').css('visibility', 'hidden');
                $('.modal-overlay').hide();
                $('body').removeClass('body-no-scroll');
            }
        );
    });
});
