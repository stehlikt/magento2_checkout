require([
    'jquery',
    'jquery/validate',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/alert'
], function ($, validate, customerData,alert) {
    'use strict';
    $(document).ready(function () {

        var hash = window.location.hash;

        if (hash == '#step1') {
            $('#step1').show();
            $('#step2').hide();
            $('.cart-items').addClass('cart-items-width');
            $('.quantity-button').show();
            $('.quantity-input').attr('readonly', false);
        } else if (hash == '#summary') {
            $('#step2').show();
            $('.cart-items').removeClass('cart-items-width');
            $('#step1, .coupon-col, .cart-actions, .quantity-button, .remove-cart-item').hide();
            $('.quantity-input').attr('readonly', true);
        }

        $(document).on('click', '#next-step', function () {
            $('#step1, .coupon-col, .cart-actions, .quantity-button, .remove-cart-item').hide();
            $('#step2').show();
            $('.cart-items').removeClass('cart-items-width');
            $('.quantity-input').attr('readonly', true);
            window.location.href = window.location.href+'#summary';
        });

        if ($('.customer-type-option.active').data('value') == 'individual') {
            $('.company-row, .company, .vat, .tax').hide();
        }

        $('.login-container form').append('<input type="hidden" name="checkout" value="true">');

        $(document).on('click', '.save-order', function () {

            var orderForm = $('#order-form');
            var validateShipping = validateShippingAndPaymentOption();

            if (orderForm.valid() && validateShipping) {
                var formData = orderForm.find('input, select, textarea')
                    .not('[name^="billing_"], [name^="shipping_"]')
                    .serialize();

                formData = formData + '&shipping_method=' + $('.shipping-option:checked').val();

                if($('#save_new_billing_address').is(':checked'))
                    formData = formData + '&save_new_billing_address=' + $('#save_new_billing_address').val();
                if($('#save_new_shipping_address').is(':checked'))
                    formData = formData + '&save_new_shipping_address=' + $('#save_new_shipping_address').val();
                $('body').trigger('processStart');
                console.log(formData);
                $.ajax({
                    url: '/railsformers_checkout/checkout/saveorder',
                    type: 'POST',
                    dataType: 'json',
                    data: {orderData: formData},
                    success: function (response) {
                        if (response.success) {
                            $('body').trigger('processStop');
                            var sections = ['cart'];
                            customerData.invalidate(sections);
                            customerData.reload(sections, true);
                            window.location.href = response.redirect;
                        } else {
                            $('body').trigger('processStop');
                            alert({
                                title: 'Chyba objednávky',
                                content: 'Objednávku nebylo možno dokončit. Zkuste to prosím znovu, nebo kontaktujte podporu.',
                                actions: {
                                    always: function(){}
                                }
                            });
                            console.log(response.message);
                        }
                    }
                });
            } else {
                orderForm.find('input:invalid, select:invalid, textarea:invalid').first().focus();
                $('input.mage-error').css('border-bottom', '2px solid #e02b27');

            }
        });
        $('input').keyup(function (){
            if ($(this).hasClass('mage-error')) {
                $(this).css('border-bottom', '');
                $(this).next('div.mage-error').hide();
            }
        });

        $('.customer-type-option').click(function () {
            $('.customer-type-option').removeClass('active');
            $(this).addClass('active');

            var selectedValue = $(this).data('value');
            if (selectedValue == 'individual') {
                $('.company-row, .company, .vat, .tax').hide();
            } else if (selectedValue == 'company') {
                $('.company-row, .company, .vat, .tax').show();
            }
        });

        $("#same-as-billing").on("change", function () {
            if ($("#same-as-billing").is(":checked")) {
                $(".shipping-form-div :input").not("#same-as-billing").prop("disabled", true);
                $(".shipping-form-div").attr("novalidate", "novalidate").css('opacity', '0.5');
            } else {
                $(".shipping-form-div :input").not("#same-as-billing").prop("disabled", false);
                $(".shipping-form-div").removeAttr("novalidate").css('opacity', '1');
            }
        });

        $('.account-option').click(function () {
            $('.account-option').removeClass('active');
            $(this).addClass('active');

            var selectedValue = $(this).data('value');
            if (selectedValue == 'login') {
                $('.login-container').show();
            } else {
                $('.login-container').hide();
            }
        });

        $('.action-hide-popup, .action-close').click(function() {
            closeModal();
        });

        $(document).on('click', '#add-new-billing-address', function () {
            openBillingModal();
        });

        $(document).on('click', '#add-new-shipping-address', function () {
            openShippingModal();
        });

        if ($('input[type="radio"][name="selected_address_billing"]:checked').length > 0) {
            updateSelectedBillingAddress();
        }

        $(document).on('change', 'input[type="radio"][name="selected_address_billing"]', function() {
            updateSelectedBillingAddress();
        });

        if ($('input[type="radio"][name="selected_address_shipping"]:checked').length > 0) {
            updateSelectedShippingAddress();
        }

        $(document).on('change', 'input[type="radio"][name="selected_address_shipping"]', function() {
            updateSelectedShippingAddress()
        });

        $('.action-save-billing-address').on('click', function() {
            var billingAddressForm = $('.form-billing-address');
            if(billingAddressForm.valid())
            {
                var firstname = $('#nb-firstname').val();
                var lastname = $('#nb-lastname').val();
                var company = $('#nb-company').val();
                var street = $('#nb-street').val();
                var city = $('#nb-city').val();
                var postcode = $('#nb-postcode').val();
                var country = $('#nb-country').find('option:selected').val();
                var telephone = $('#nb-telephone').val();
                var vat = $('#nb-ic').val();
                var dic = $('#nb-dic').val();

                var lastIndex = parseInt($('input[type="radio"][name="selected_address_billing"]:last').val());
                var newIdndex = isNaN(lastIndex) ? 1 : lastIndex + 1;

                $('.new-billing-address').remove();
                $('#add-new-billing-address').remove();

                var newDiv = $('<div class="new-billing-address billing-address"></div>');
                var labelContent = '<div class="address-details">' + firstname + ' ' + lastname + '<br>' + company + '<br>' + 'IČ: ' + vat  + '<br> DIČ' + dic + '<br>' + street + '<br>' + city + ', ' + postcode + '<br>' + country + '<br>' + telephone + '</div>';
                var label = $('<label class="select-address-label"></label>').html(labelContent);
                var radioInput = $('<input>', {
                    type: 'radio',
                    name: 'selected_address_billing',
                    value: newIdndex,
                    checked: true
                });

                label.prepend(radioInput);
                newDiv.append(label);
                newDiv.append($('<input type="hidden" name="billing_firstname_'+newIdndex+'" value="' + firstname + '">'));
                newDiv.append($('<input type="hidden" name="billing_lastname_'+newIdndex+'" value="' + lastname + '">'));
                newDiv.append($('<input type="hidden" name="billing_company_'+newIdndex+'" value="' + company + '">'));
                newDiv.append($('<input type="hidden" name="billing_street_'+newIdndex+'" value="' + street + '">'));
                newDiv.append($('<input type="hidden" name="billing_city_'+newIdndex+'" value="' + city + '">'));
                newDiv.append($('<input type="hidden" name="billing_postcode_'+newIdndex+'" value="' + postcode + '">'));
                newDiv.append($('<input type="hidden" name="billing_country_'+newIdndex+'" value="' + country + '">'));
                newDiv.append($('<input type="hidden" name="billing_telephone_'+newIdndex+'" value="' + telephone + '">'));
                newDiv.append($('<input type="hidden" name="billing_vat_'+newIdndex+'" value="' + vat + '">'));
                newDiv.append($('<input type="hidden" name="billing_tax_'+newIdndex+'" value="' + dic + '">'));
                newDiv.append('<a id="add-new-billing-address">Upravit</a>')

                $('.select-billing-address').first().append(newDiv);
                updateSelectedBillingAddress();
                closeModal();
            }
        });

        $('.action-save-shipping-address').on('click', function() {
            var shippingAddressForm = $('.form-shipping-address');
            if(shippingAddressForm.valid())
            {
                var firstname = $('#ns-firstname').val();
                var lastname = $('#ns-lastname').val();
                var company = $('#ns-company').val();
                var street = $('#ns-street').val();
                var city = $('#ns-city').val();
                var postcode = $('#ns-postcode').val();
                var country = $('#ns-country').find('option:selected').val();
                var telephone = $('#ns-telephone').val();
                var vat = $('#ns-ic').val();
                var dic = $('#ns-dic').val();

                var lastIndex = parseInt($('input[type="radio"][name="selected_address_shipping"]:last').val());
                var newIdndex = isNaN(lastIndex) ? 1 : lastIndex + 1;

                $('.new-shipping-address').remove();
                $('#add-new-shipping-address').remove();
                $('.address-button-div').remove();

                var newDiv = $('<div class="new-shipping-address shipping-address"></div>');
                var labelContent = '<div class="address-details">' + firstname + ' ' + lastname + '<br>' + company + '<br>' + 'IČ: ' + vat  + '<br> DIČ' + dic + '<br>' + street + '<br>' + city + ', ' + postcode + '<br>' + country + '<br>' + telephone + '</div>';
                var label = $('<label class="select-address-label"></label>').html(labelContent);
                var radioInput = $('<input>', {
                    type: 'radio',
                    class: 'selected-address-shipping',
                    name: 'selected_address_shipping',
                    value: newIdndex,
                    checked: true
                });

                label.prepend(radioInput);
                newDiv.append(label);
                newDiv.append($('<input type="hidden" name="shipping_firstname_'+newIdndex+'" value="' + firstname + '">'));
                newDiv.append($('<input type="hidden" name="shipping_lastname_'+newIdndex+'" value="' + lastname + '">'));
                newDiv.append($('<input type="hidden" name="shipping_company_'+newIdndex+'" value="' + company + '">'));
                newDiv.append($('<input type="hidden" name="shipping_street_'+newIdndex+'" value="' + street + '">'));
                newDiv.append($('<input type="hidden" name="shipping_city_'+newIdndex+'" value="' + city + '">'));
                newDiv.append($('<input type="hidden" name="shipping_postcode_'+newIdndex+'" value="' + postcode + '">'));
                newDiv.append($('<input type="hidden" name="shipping_country_'+newIdndex+'" value="' + country + '">'));
                newDiv.append($('<input type="hidden" name="shipping_telephone_'+newIdndex+'" value="' + telephone + '">'));
                newDiv.append($('<input type="hidden" name="shipping_vat_'+newIdndex+'" value="' + vat + '">'));
                newDiv.append($('<input type="hidden" name="shipping_tax_'+newIdndex+'" value="' + dic + '">'));
                newDiv.append('<a id="add-new-shipping-address">Upravit</a>')

                $('.select-shipping-address').first().append(newDiv);
                updateSelectedShippingAddress();
                closeModal();
            }
        });

        $('#heureka').on('change', function (){
            if($('input#heureka').is(':checked'))
                $.cookie('heurekaDisabled', 1, {expires: 86400});
            else
                $.cookie('heurekaDisabled', 0, {expires: 86400});
            console.log($.cookie('heurekaDisabled'));
        });

        function updateSelectedBillingAddress() {
            var index = $('input[type="radio"][name="selected_address_billing"]:checked').val();
            var fields = ['firstname', 'lastname', 'company', 'city', 'street', 'postcode', 'country', 'telephone' , 'vat', 'tax'];

            fields.forEach(function(field) {
                $('input[name="billing[' + field + ']"]').val($('input[name="billing_' + field + '_' + index + '"]').val());
            });
        }

        function updateSelectedShippingAddress() {
            var index = $('input[type="radio"][name="selected_address_shipping"]:checked').val();
            var fields = ['firstname', 'lastname', 'company', 'city', 'street', 'postcode', 'country', 'telephone' , 'vat', 'tax'];

            fields.forEach(function(field) {
                $('input[name="shipping[' + field + ']"]').val($('input[name="shipping_' + field + '_' + index + '"]').val());
            });
        }
        function validateShippingAndPaymentOption(){
            if ($('.shipping-option:checked').length > 0 && $('.payment-option:checked').length > 0) {
                if ($('.shipping-option:checked').val() == 'parcelshop' && $('.parcelshop-pickup-point').val() == '') {
                    alert({
                        title: 'Chyba',
                        content: 'Vyberte prosím adresu doručení',
                        actions: {
                            always: function () { }
                        }
                    });
                    $('html, body').animate({
                        scrollTop: $(".shipping-payment-container").offset().top
                    }, 500);
                    return false;
                } else {
                    return true;
                }

            }
            else {
                alert({
                    title: 'Chyba',
                    content: 'Vyberte prosím možnost dopravy a platby',
                    actions: {
                        always: function () { }
                    }
                });
                $('html, body').animate({
                    scrollTop: $(".shipping-payment-container").offset().top
                }, 500);
                return false;
            }
        }
        
        function openBillingModal() {
            $('.modal-popup-billing').show();
            $('.modal-overlay').show();
            $('body').addClass('body-no-scroll');
        }

        function closeModal() {
            $('.modal-popup-billing, .modal-popup-shipping').hide();
            $('.modal-overlay').hide();
            $('body').removeClass('body-no-scroll');
        }

        function openShippingModal() {
            $('.modal-popup-shipping').show();
            $('.modal-overlay').show();
            $('body').addClass('body-no-scroll');
        }
    });
});
