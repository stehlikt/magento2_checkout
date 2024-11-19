 require([
     'jquery'
 ], function ($) {
     'use strict';
     $(document).ready(function() {
         var couponPostUrl = $('#coupon-post-url').val();
         $(document).on('click', '#apply_coupon', function (){
             var couponCode = $('#coupon_code').val();
             if (couponCode) {
                 $.ajax({
                     url: couponPostUrl,
                     type: 'post',
                     data: {coupon_code: couponCode},
                     success: function(response) {
                         location.reload();
                     }
                 });
             }
         });
         $(document).on('click', '#remove_coupon', function (){
             $.ajax({
                 url: couponPostUrl,
                 type: 'post',
                 data: {remove: 1},
                 success: function(response) {
                     location.reload();
                 }
             });
         });
     });
 });
