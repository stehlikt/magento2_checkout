require([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';
    $(document).ready(function() {

        var updateCartUrl = $('#update-cart-url').val();

        $(document).on('click', '#update-cart-btn', function (){
            var formData = $('.cart-container .cart-items table input[name^="qty"]').serialize();
            $.ajax({
                url: updateCartUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        reloadCart()
                        location.reload();
                    } else {
                        console.log(response.error);
                    }
                }
            });
        });

        $(document).on('click', '.quantity-minus', function (){
            $('body').trigger('processStart');
            var itemId = $(this).data('item-id');
            var input = $('.cart-container .cart-items table input[name="qty[' + itemId + ']"]');
            var currentValue = parseInt(input.val());
            if (currentValue > 1) {
                input.val(currentValue - 1);
            }

            $.ajax({
                url: updateCartUrl,
                type: 'POST',
                data: input.serialize(),
                success: function(response) {
                    $('.cart-items').replaceWith(response.itemsHtml);
                    $('.summary').replaceWith(response.summaryHtml);
                    $('.summary').applyBindings();
                    $('.final-summary').replaceWith(response.finalSummaryHtml);
                    reloadCart();
                    $('body').trigger('processStop');
                }
            });

        });

        $(document).on('click', '.quantity-plus', function (){
            $('body').trigger('processStart');
            var itemId = $(this).data('item-id');
            var input = $('.cart-container .cart-items table input[name="qty[' + itemId + ']"]');
            var currentValue = parseInt(input.val());
            input.val(currentValue + 1);

            $.ajax({
                url: updateCartUrl,
                type: 'POST',
                data: input.serialize(),
                success: function(response) {
                    $('.cart-items').replaceWith(response.itemsHtml);
                    $('.summary').replaceWith(response.summaryHtml);
                    $('.summary').applyBindings();
                    $('.final-summary').replaceWith(response.finalSummaryHtml);
                    reloadCart();
                    $('body').trigger('processStop');
                },
            });
        });

        $(document).on('click', '.remove-cart-item', function(event) {
            event.preventDefault();

            var removeUrl = $(this).attr('href');

            $.ajax({
                url: removeUrl,
                type: 'POST',
                success: function (response) {
                    if(response.success)
                    {
                        reloadCart()
                        location.reload();
                    }
                }
            });
        });

        function reloadCart()
        {
            var sections = ['cart'];
            customerData.invalidate(sections);
            customerData.reload(sections, true);
        }
    });
});