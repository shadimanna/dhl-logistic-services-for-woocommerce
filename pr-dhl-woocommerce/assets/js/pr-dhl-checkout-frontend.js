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
      $( document.body ).on( 'click', '#dhl_parcel_finder', this.init_form );
      $( 'form.checkout_dhl_parcel_finder' ).submit( this.submit );
    },
    init_form: function() {
      /*
      var gmap_city = $('.woocommerce-checkout #shipping_city').val();
      console.log(gmap_city);
      if ( ! gmap_city ) {
        gmap_city = $('.woocommerce-checkout #billing_city').val();
        console.log(gmap_city);
      }
      $('#dhl_billing_postcode').val(gmap_postcode);
      */
      var gmap_country = $('.woocommerce-checkout #shipping_country').val();
      console.log(gmap_country);
      if ( ! gmap_country ) {
        gmap_country = $('.woocommerce-checkout #billing_country').val();
      }
      $('#dhl_parcelfinder_country').val( gmap_country );

      var gmap_postcode = $('.woocommerce-checkout #shipping_postcode').val();
      console.log(gmap_postcode);
      if ( ! gmap_postcode ) {
        gmap_postcode = $('.woocommerce-checkout #billing_postcode').val();
      }
      $('#dhl_parcelfinder_postcode').val( gmap_postcode );

      var gmap_city = $('.woocommerce-checkout #shipping_city').val();
      console.log(gmap_city);
      if ( ! gmap_city ) {
        gmap_city = $('.woocommerce-checkout #billing_city').val();
      }
      $('#dhl_parcelfinder_city').val( gmap_city );

      var gmap_address_1 = $('.woocommerce-checkout #shipping_address_1').val();
      console.log(gmap_address_1);
      if ( ! gmap_address_1 ) {
        gmap_address_1 = $('.woocommerce-checkout #billing_address_1').val();
      }

      var gmap_address_2 = $('.woocommerce-checkout #shipping_address_2').val();
      console.log(gmap_address_2);
      if ( ! gmap_address_2 ) {
        gmap_address_2 = $('.woocommerce-checkout #billing_address_2').val();
      }

      var gmap_address = gmap_address_1 + ' ' + gmap_address_2;

      $('#dhl_parcelfinder_address').val( gmap_address );

      $( 'form.checkout_dhl_parcel_finder' ).submit();
    },
    submit: function() {
      var $form = $( this );
      
      $('#dhl_parcel_finder_form .checkout_dhl_parcel_finder .woocommerce-error').remove();

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
        action:                   'wc_shipment_dhl_parcelfinder_search',
        parcelfinder_country:     $('#dhl_parcelfinder_country').val(),
        parcelfinder_postcode:    $('#dhl_parcelfinder_postcode').val(),
        parcelfinder_city:        $('#dhl_parcelfinder_city').val(),
        parcelfinder_address:     $('#dhl_parcelfinder_address').val(),
        security:                 $form.find( 'input[name="dhl_parcelfinder_nonce"]' ).val()
      };

      $.ajax({
        type:   'POST',
        url:    pr_dhl_checkout_frontend.ajax_url,
        data:   data,
        success:  function( parcelshops ) {
          $( '.woocommerce-error, .woocommerce-message' ).remove();
          $form.removeClass( 'processing' ).unblock();
          // console.log(parcelshops);
          if ( parcelshops ) {
            // alert(code);
            var parcelshopsJS = JSON.parse( parcelshops );

            // console.log(parcelshopsJS);
            // console.log(parcelshopsJS.error);
            // $form.before( code );
            // $form.slideUp();
            if( parcelshopsJS.error ) {
              $('#dhl_parcel_finder_form .checkout_dhl_parcel_finder').append('<div class="woocommerce-error">' + parcelshopsJS.error + '</div>');
            } else {
              // JSON parse returned results
              wc_checkout_dhl_parcelfinder.populateMap( parcelshopsJS.parcel_res );
            }
            // $( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
          }
        },
        dataType: 'html'
      });

      return false;
    },
    populateMap: function( parcelshops ) {
      // alert('populateMap');
      // var parcel_res_js = JSON.parse( parcelshops );
      // console.log(parcel_res_js);
      if( ! parcelshops ) {
        return;
      }

      var uluru = {lat: parcelshops[0].location.latitude, lng: parcelshops[0].location.longitude };
      var map = new google.maps.Map(document.getElementById('dhl_google_map'), {
        zoom: 13,
        center: uluru
      });

      $.each(parcelshops, function(key,value) {
        // console.log(key);
        // console.log(value);
        // var uluru = {lat: -25.363, lng: 131.044};
        var uluru = {lat: value.location.latitude, lng: value.location.longitude};
        /*
          Title
          Address
          Opening hours
          Select button
        
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
                '</div>';*/

        // var contentString = value.location.psfTimeinfos;

        // Get opening times
        var openingTimes = '<h5 class="parcel_subtitle">' + pr_dhl_checkout_frontend.opening_times + '</h5>';
        $.each(value.psfTimeinfos, function(key_times,value_times) {
          // console.log(key_times);
          console.log(value_times);
          if( value_times.type == 'openinghour' ) {
            console.log(value_times.weekday);
            switch (value_times.weekday) {
              case 1:
                openingTimes += pr_dhl_checkout_frontend.monday;
                break;
              case 2:
                openingTimes += pr_dhl_checkout_frontend.tueday;
                break; 
              case 3:
                openingTimes += pr_dhl_checkout_frontend.wednesday;
                break;
              case 4:
                openingTimes += pr_dhl_checkout_frontend.thrusday;
                break;
              case 5:
                openingTimes += pr_dhl_checkout_frontend.friday;
                break; 
              case 6:
                openingTimes += pr_dhl_checkout_frontend.satuday;
                break;
              case 7:
                openingTimes += pr_dhl_checkout_frontend.sunday;
                break;
              
            }

            openingTimes += value_times.timefrom + ' - ' + value_times.timeto + '<br/>';
          }
          
        });

        // Get services
        var shopServices = '<h5 class="parcel_subtitle">' + pr_dhl_checkout_frontend.services + '</h5>';
        var shopServicesParking = pr_dhl_checkout_frontend.no;
        var shopServicesHandicap = pr_dhl_checkout_frontend.no;
        $.each(value.psfServicetypes, function(key_services,value_services) {
          // console.log(key_times);
          // console.log(value_times);
          switch (value_services) {
            case 'parking':
              shopServicesParking = pr_dhl_checkout_frontend.yes;
              break;
            case 'handicappedAccess':
              shopServicesHandicap = pr_dhl_checkout_frontend.yes;
              break; 
          }

        });
        
        shopServices += pr_dhl_checkout_frontend.parking + shopServicesParking + '<br/>';
        shopServices += pr_dhl_checkout_frontend.handicap + shopServicesHandicap + '<br/>';

        var contentString = '<div id="content">'+
                '<div id="siteNotice">'+
                '</div>'+
                '<h4 class="parcel_title">' + value.shopName + '</h4>'+
                '<div id="bodyContent">'+
                '<p>' + value.street + ' ' + value.houseNo + '</br>' + value.city + ' ' + value.zipCode + '</p>'+
                openingTimes +
                shopServices +
                '</div>'+
                '</div>';

        var infowindow = new google.maps.InfoWindow({
              content: contentString,
              maxWidth: 200
            });

        switch (value.shopType) {
          case 'packStation':
            var gmap_marker_icon = pr_dhl_checkout_frontend.packstation_icon;
            break;
          case 'parcelShop':
            var gmap_marker_icon = pr_dhl_checkout_frontend.parcelshop_icon;
            break; 
          case 'postOffice':
            var gmap_marker_icon = pr_dhl_checkout_frontend.post_office_icon;
            break;
          default:
            var gmap_marker_icon = pr_dhl_checkout_frontend.packstation_icon;
            break;
        }
        var marker = new google.maps.Marker({
          position: uluru,
          map: map,
          title: 'DHL Parcel',
          animation: google.maps.Animation.DROP,
          icon: gmap_marker_icon
        });
        
        marker.addListener('click', function() {
          infowindow.open(map, marker);
        });

      }); 


      // marker.addListener('click', toggleBounce);
    }
  };

  // initParcelFinderMap();

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
