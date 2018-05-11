jQuery(function($) {

  var $doc = $(document);
  var $body = $(document.body);
  $doc.on('updated_checkout', function() {

    $('.pr_dhl_preferred_day').on('change', function() {
      $body.trigger('update_checkout');
    });

    $('.pr_dhl_preferred_time').on('change', function() {
      $body.trigger('update_checkout');
    });

    if( pr_dhl_checkout_frontend.cod_enabled ) {
      jQuery('.woocommerce').on('change', '.payment_methods .input-radio', function() {
        $body.trigger('update_checkout');
      });
    }

    // reveal tooltips on hover
    $('.dhl-tooltip').tooltip();
    // no blacklist strings in DHL inputs
    var blacklist = ['Paketbox', 'Packstation', 'Postfach', 'Postfiliale', 'Filiale', 'Postfiliale Direkt', 'Filiale Direkt', 'Paketkasten', 'DHL', 'P-A-C-K-S-T-A-T-I-O-N', 'Paketstation', 'Pack Station', 'P.A.C.K.S.T.A.T.I.O.N.', 'Pakcstation', 'Paackstation', 'Pakstation', 'Backstation', 'Bakstation', 'P A C K S T A T I O N', 'Wunschfiliale', 'Deutsche Post', '<', '>', '\\n', '\\r', /\\/, '\'', '"', ';', /\+/];
    var textInputs = $('#pr_dhl_preferred_neighbour_name, #pr_dhl_preferred_neighbour_address, #pr_dhl_preferred_location');
    textInputs.keyup(function(e) {
      var val = e.target.value;
      for (var i = 0, len = blacklist.length; i < len; i++) {
        var item = blacklist[i];
        if (val.match(item) !== null) {
          val = val.replace(item, '');
          i--;
        }
      }
      e.target.value = val;
    });

    // reveal the location or neighbor section on radio change
    var LONselector = 'input[name="pr_dhl_preferred_location_neighbor"]';
    var LONradioButtons = $(LONselector);
    // toggle location / locatoin sections if user has a choice
    if (LONradioButtons.length > 0) {
      var initialLONvalue = $(LONselector + ':checked').val();
      var LONsections = $('.dhl-co-tr.dhl-radio-toggle');
      LONsections.each(function(i, el) {
        var section = $(el);
        if (!section.hasClass('dhl-' + initialLONvalue)) {
          section.hide();
        }
      });
      LONradioButtons.on('change', function(e) {
        var classToMatch = 'dhl-' + e.target.value;
        LONsections.each(function(i, sect){
          var section = $(sect);
          if (section.hasClass(classToMatch)) {
            var inputs = section.find('input');
            inputs.each(function(i, el){
              if (el.dataset.onhold) {
                el.value = el.dataset.onhold;
              }
            });
            section.show();
          } else {
            section.hide();
            section.find('input').each(function(i, el){
              if (el.value.length > 0) {
                el.dataset.onhold = el.value;
                el.value = '';
              }
            });
          }
        });
      });
    }
  });
});

// Load fancybox
jQuery(document).ready(function($) {

  // var marker;

  function initParcelFinderMap() {
    var uluru = {lat: -25.363, lng: 131.044};
    var map = new google.maps.Map(document.getElementById('dhl_google_map'), {
      zoom: 10,
      center: uluru
    });

    /*
      Title
      Address
      Opening hours
      Select button
    */
    var contentString = '<div id="content">'+
            '<div id="siteNotice">'+
            '</div>'+
            '<h1 id="firstHeading" class="firstHeading">Uluru</h1>'+
            '<div id="bodyContent">'+
            '<p><b>Uluru</b>, also referred to as <b>Ayers Rock</b>, is a large ' +
            'sandstone rock formation in the southern part of the '+
            'Northern Territory, central Australia. It lies 335&#160;km (208&#160;mi) '+
            'south west of the nearest large town, Alice Springs; 450&#160;km '+
            '(280&#160;mi) by road. Kata Tjuta and Uluru are the two major '+
            'features of the Uluru - Kata Tjuta National Park. Uluru is '+
            'sacred to the Pitjantjatjara and Yankunytjatjara, the '+
            'Aboriginal people of the area. It has many springs, waterholes, '+
            'rock caves and ancient paintings. Uluru is listed as a World '+
            'Heritage Site.</p>'+
            '<p>Attribution: Uluru, <a href="https://en.wikipedia.org/w/index.php?title=Uluru&oldid=297882194">'+
            'https://en.wikipedia.org/w/index.php?title=Uluru</a> '+
            '(last visited June 22, 2009).</p>'+
            '</div>'+
            '</div>';

    var infowindow = new google.maps.InfoWindow({
          content: contentString,
          maxWidth: 200
        });

    var marker = new google.maps.Marker({
      position: uluru,
      map: map,
      title: 'DHL Parecel',
      animation: google.maps.Animation.DROP,
      icon: 'http://dhlplugin:8888/wp-content/uploads/2018/01/image-1.png'
    });

    marker.addListener('click', function() {
      infowindow.open(map, marker);
    });

    // marker.addListener('click', toggleBounce);
  }
/*
  function toggleBounce() {
    if (marker.getAnimation() !== null) {
      marker.setAnimation(null);
    } else {
      marker.setAnimation(google.maps.Animation.BOUNCE);
    }
  }
*/
  var wc_checkout_dhl_parcelfinder = {
    init: function() {
      // $( document.body ).on( 'click', 'a.showcoupon', this.show_coupon_form );
      // $( document.body ).on( 'click', '.woocommerce-remove-coupon', this.remove_coupon );
      $( 'form.checkout_dhl_parcel_finder' ).submit( this.submit );
    },
    submit: function() {
      var $form = $( this );

      if ( $form.is( '.processing' ) ) {
        return false;
      }

      $form.addClass( 'processing' ).block({
        message: null,
        overlayCSS: {
          background: '#fff',
          opacity: 0.6
        }
      });

      var data = {
        action:             'wc_shipment_dhl_parcelfinder_search',
        billing_postcode:   $form.find( 'input[name="dhl_billing_postcode"]' ).val(),
        security:           $form.find( 'input[name="dhl_parcelfinder_nonce"]' ).val()
      };

      $.ajax({
        type:   'POST',
        url:    pr_dhl_checkout_frontend.ajax_url,
        data:   data,
        success:  function( code ) {
          $( '.woocommerce-error, .woocommerce-message' ).remove();
          $form.removeClass( 'processing' ).unblock();

          if ( code ) {
            // alert(code);
            // $form.before( code );
            // $form.slideUp();

            // $( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
          }
        },
        dataType: 'html'
      });

      return false;
    }
  };

  initParcelFinderMap();

  /* This is basic - uses default settings */
  $("a#dhl_parcel_finder").fancybox({
    'autoDimensions' : false,
    'width' : 800,
    'height' : 800
    });
  
  
  wc_checkout_dhl_parcelfinder.init();


  /* Using custom settings 
  
  $("a#inline").fancybox({
    'hideOnContentClick': true
  });*/

  /* Apply fancybox to multiple items 
  
  $("a.group").fancybox({
    'transitionIn'  : 'elastic',
    'transitionOut' : 'elastic',
    'speedIn'   : 600, 
    'speedOut'    : 200, 
    'overlayShow' : false
  });*/
  
});
