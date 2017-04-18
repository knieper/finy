/**
 * @file
 * Custom scripts for theme.
 */
(function ($) {

  // sendowl cart
  Drupal.behaviors.sendOwl = {
    attach: function (context) {
      sendOwl.cartWidget({merchantID: 31069,
        parent: $(SendOwlCart)[0],
        cartPhrase: "Your Cart - ",
        useCartImage: false,
        customStyle: true
      });
      //console.log('Hello World test');
    }
  }

})(jQuery);
