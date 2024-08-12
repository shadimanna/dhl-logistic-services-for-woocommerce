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

    if ( $('.dhl-tooltip').length > 0 ) {
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
    }
  });
});

function gm_authFailure() {
  // alert('gm_authFailure');
  jQuery('.woocommerce-checkout #dhl_parcel_finder_form #dhl_google_map').before( pr_dhl_checkout_frontend.no_api_key );
};

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
    updateTimer: false,
    init: function() {
      // $( document.body ).on( 'click', 'a.showcoupon', this.show_coupon_form );
      $( document.body ).on( 'click', '#dhl_parcel_finder', this.init_form );

      $( document.body ).on( 'click', '.parcelshop-select-btn', this.selectedShop );

      $( document.body ).on( 'change', '#shipping_dhl_address_type', this.address_type );

      $( document.body ).on( 'change', '#shipping_dhl_drop_off', this.selectedDropOff );

      wc_checkout_dhl_parcelfinder.address_type();
      wc_checkout_dhl_parcelfinder.populateDropdown();

      $( 'form#checkout_dhl_parcel_finder' ).submit( this.submit );
    },
    address_type: function() {
        var address_type = $('.woocommerce-checkout #shipping_dhl_address_type').val();

        if (address_type == 'dhl_packstation') {
          $( '.woocommerce-checkout #shipping_dhl_postnum_field' ).show();
          // If does not have span or span with "required" class, add it
          if ( ! $( '.woocommerce-checkout #shipping_dhl_postnum_field label span' ).length ||
             ( ! $( '.woocommerce-checkout #shipping_dhl_postnum_field label span' ).hasClass('required') ) ) {
              $( '.woocommerce-checkout #shipping_dhl_postnum_field label' ).append(' <span class="required">*</span>');
          }
        } else if (address_type == 'dhl_branch') {
          $( '.woocommerce-checkout #shipping_dhl_postnum_field' ).show();
          // remove "required" span tag
          $( '.woocommerce-checkout #shipping_dhl_postnum_field label .required' ).remove();
        } else {
          $( '.woocommerce-checkout #shipping_dhl_postnum_field' ).hide();
        }
    },
    init_form: function() {

        var gmap_country = '';
        if ( $('.woocommerce-checkout #billing_country').length ) {
            gmap_country = $('.woocommerce-checkout #billing_country').val();
        }
        $('#dhl_parcelfinder_country').val( gmap_country );

        var gmap_postcode = '';
        if ( $('.woocommerce-checkout #billing_postcode').length ) {
            gmap_postcode = $('.woocommerce-checkout #billing_postcode').val();
        }
        $('#dhl_parcelfinder_postcode').val( gmap_postcode );

        var gmap_city = '';
        if ( $('.woocommerce-checkout #billing_city').length ) {
            gmap_city = $('.woocommerce-checkout #billing_city').val();
        }
         $('#dhl_parcelfinder_city').val( gmap_city );

        var gmap_address_1 = '';
        if ( $('.woocommerce-checkout #billing_address_1').length ) {
            gmap_address_1 = $('.woocommerce-checkout #billing_address_1').val();
        }
        var gmap_address_2 = '';
        if ( $('.woocommerce-checkout #billing_address_2').length ) {
            gmap_address_2 = $('.woocommerce-checkout #billing_address_2').val();
        }

        var gmap_address = gmap_address_1 + ' ' + gmap_address_2;
        gmap_address = gmap_address.trim();

      $('#dhl_parcelfinder_address').val( gmap_address );

      $( 'form#checkout_dhl_parcel_finder' ).submit();


    },
    submit: function() {
      var $form = $( this );

      $('#dhl_parcel_finder_form #checkout_dhl_parcel_finder .woocommerce-error').remove();

      if ( $form.is( '.processing' ) ) {
        return false;
      }

      var pf_post_code = $('#dhl_parcelfinder_postcode').val();
      if( ! pf_post_code ) {
        $('#dhl_parcel_finder_form #checkout_dhl_parcel_finder').append('<div class="woocommerce-error">' + pr_dhl_checkout_frontend.post_code_error + '</div>');
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
        parcelfinder_postcode:    pf_post_code,
        parcelfinder_city:        $('#dhl_parcelfinder_city').val(),
        parcelfinder_address:     $('#dhl_parcelfinder_address').val(),
        packstation_filter:       $('#dhl_packstation_filter').is(":checked"),
        branch_filter:            $('#dhl_branch_filter').is(":checked"),
        security:                 $form.find( 'input[name="dhl_parcelfinder_nonce"]' ).val()
      };

      $.ajax({
        type:   'POST',
        url:    pr_dhl_checkout_frontend.ajax_url,
        data:   data,
        success:  function( parcelShopsJSON ) {
          $( '.woocommerce-error, .woocommerce-message' ).remove();
          $form.removeClass( 'processing' ).unblock();
          if ( parcelShopsJSON ) {
            var parcelShopsRes = JSON.parse( parcelShopsJSON );

            if( parcelShopsRes.error ) {
              $('#dhl_parcel_finder_form #checkout_dhl_parcel_finder').append('<div class="woocommerce-error">' + parcelShopsRes.error + '</div>');
            } else {
              // JSON parse returned results
              wc_checkout_dhl_parcelfinder.parcelShops = parcelShopsRes.parcel_res;
              wc_checkout_dhl_parcelfinder.populateMap();
            }
            // $( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
          }
        },
        dataType: 'html'
      });

      return false;
    },
    populateDropdown: function() {
     
      var pf_post_code = $('#dhl_parcelfinder_postcode').val();
     

      var data = {
        action:                   'wc_shipment_dhl_parcelfinder_search',
        parcelfinder_country:     $('#billing_country').val(),
        parcelfinder_postcode:    pf_post_code,
        parcelfinder_city:        $('#billing_city').val(),
        parcelfinder_address:     $('#billing_address_1').val(),
        packstation_filter:       $('#dhl_packstation_filter').is(":checked"),
        branch_filter:            $('#dhl_branch_filter').is(":checked"),
        security:                 $( 'form#checkout_dhl_parcel_finder' ).find( 'input[name="dhl_parcelfinder_nonce"]' ).val()
      };

      $.ajax({
        type:   'POST',
        url:    pr_dhl_checkout_frontend.ajax_url,
        data:   data,
        success:  function( parcelShopsJSON ) {
          $( '.woocommerce-error, .woocommerce-message' ).remove();
          if ( parcelShopsJSON ) {
            var parcelShopsRes = JSON.parse( parcelShopsJSON );

            if( parcelShopsRes.error ) {
              $('#dhl_parcel_finder_form #checkout_dhl_parcel_finder').append('<div class="woocommerce-error">' + parcelShopsRes.error + '</div>');
            } else {
              // JSON parse returned results
              // Find the dropdown element in the DOM
              const dropdown = document.getElementById('shipping_dhl_drop_off');
              if (dropdown) {                
                wc_checkout_dhl_parcelfinder.parcelShops = parcelShopsRes.parcel_res;
                // Populate the dropdown with parcel shop names
                wc_checkout_dhl_parcelfinder.parcelShops.forEach(element => {
                    // Create a new option element
                    const option = document.createElement('option');
                    option.value = element.location.ids[0].locationId; // Use a relevant value if needed
                    option.text = element.name;  // Display the name as the text of the option

                    // Add the new option to the dropdown
                    dropdown.add(option);
                });
              }
               
            }
          }
        },
        dataType: 'html'
      });

      return false;
    },
    selectedDropOff: function() {

      var parcelShopId = $(this).val();
      $.each(wc_checkout_dhl_parcelfinder.parcelShops, function(key,value) {

        if( value.location.ids[0].locationId == parcelShopId ) {

          switch (value.location.type) {
            case 'locker':
              var shop_name = pr_dhl_checkout_frontend.packstation;
              $('.woocommerce-checkout #shipping_dhl_address_type').val('dhl_packstation').trigger('change');
              break;
            case 'servicepoint':
              var shop_name = pr_dhl_checkout_frontend.parcelShop;
              $('.woocommerce-checkout #shipping_dhl_address_type').val('dhl_branch').trigger('change');
              break;
            case 'postoffice':
              var shop_name = pr_dhl_checkout_frontend.postoffice;
              $('.woocommerce-checkout #shipping_dhl_address_type').val('dhl_branch').trigger('change');
              break;
            case 'postbank':
              var shop_name = pr_dhl_checkout_frontend.postoffice;
              $('.woocommerce-checkout #shipping_dhl_address_type').val('dhl_branch').trigger('change');
              break;
            default:
              var shop_name = pr_dhl_checkout_frontend.packstation;
              $('.woocommerce-checkout #shipping_dhl_address_type').val('dhl_packstation').trigger('change');
              break;
          }

          $('.woocommerce-checkout #shipping_first_name').val( $('.woocommerce-checkout #billing_first_name').val() );
          $('.woocommerce-checkout #shipping_last_name').val( $('.woocommerce-checkout #billing_last_name').val() );
          // $('.woocommerce-checkout #shipping_company').val( '' );
          $('.woocommerce-checkout #shipping_address_1').val( shop_name + ' ' + value.location.keywordId );
          $('.woocommerce-checkout #shipping_address_2').val( '' );
          $('.woocommerce-checkout #shipping_postcode').val( value.place.address.postalCode );
          $('.woocommerce-checkout #shipping_city').val( value.place.address.addressLocality );

          $.fancybox.close();
        }
      });
    },
    populateMap: function() {
      if( ! wc_checkout_dhl_parcelfinder.parcelShops ) {
        return;
      }

      // OSM Map
      var uluru = [wc_checkout_dhl_parcelfinder.parcelShops[0].place.geo.latitude, wc_checkout_dhl_parcelfinder.parcelShops[0].place.geo.longitude];
      var map = L.map('dhl_google_map').setView(uluru, 13);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(map);

      var infoWinArray = [];

      // Loop through each parcel shop
      $.each(wc_checkout_dhl_parcelfinder.parcelShops, function(key, value) {
        var uluru = [value.place.geo.latitude, value.place.geo.longitude]; // Leaflet uses [lat, lng]

        // Get opening times
        var openingTimes = '<h5 class="parcel_subtitle">' + pr_dhl_checkout_frontend.opening_times + '</h5>';
        var prev_day = 0;
        var day_of_week;
        $.each(value.openingHours, function(key_times, value_times) {
          switch (value_times.dayOfWeek) {
            case "http://schema.org/Monday":
              day_of_week = pr_dhl_checkout_frontend.monday;
              break;
            case "http://schema.org/Tuesday":
              day_of_week = pr_dhl_checkout_frontend.tueday;
              break;
            case "http://schema.org/Wednesday":
              day_of_week = pr_dhl_checkout_frontend.wednesday;
              break;
            case "http://schema.org/Thursday":
              day_of_week = pr_dhl_checkout_frontend.thrusday;
              break;
            case "http://schema.org/Friday":
              day_of_week = pr_dhl_checkout_frontend.friday;
              break;
            case "http://schema.org/Saturday":
              day_of_week = pr_dhl_checkout_frontend.satuday;
              break;
            case "http://schema.org/Sunday":
              day_of_week = pr_dhl_checkout_frontend.sunday;
              break;
          }

          if (prev_day) {
            if (prev_day == value_times.dayOfWeek) {
              openingTimes += ', ';
            } else {
              openingTimes += '<br/>' + day_of_week + ': ';
            }
          } else {
            openingTimes += day_of_week + ': ';
          }

          prev_day = value_times.dayOfWeek;

          openingTimes += value_times.opens + ' - ' + value_times.closes;
        });

        // Get services
        var shopServices = '<h5 class="parcel_subtitle">' + pr_dhl_checkout_frontend.services + '</h5>';
        var shopServicesParking = ': ' + pr_dhl_checkout_frontend.no;
        var shopServicesHandicap = ': ' + pr_dhl_checkout_frontend.no;
        $.each(value.serviceTypes, function(key_services, value_services) {
          switch (value_services) {
            case 'parking':
              shopServicesParking = ': ' + pr_dhl_checkout_frontend.yes;
              break;
            case 'handicapped-access':
              shopServicesHandicap = ': ' + pr_dhl_checkout_frontend.yes;
              break;
          }
        });

        shopServices += pr_dhl_checkout_frontend.parking + shopServicesParking + '<br/>';
        shopServices += pr_dhl_checkout_frontend.handicap + shopServicesHandicap + '<br/>';

        // Determine shop type and icons
        var gmap_marker_icon, shop_name, shop_label;
        switch (value.location.type) {
          case 'locker':
            gmap_marker_icon = pr_dhl_checkout_frontend.packstation_icon;
            shop_name = pr_dhl_checkout_frontend.packstation;
            shop_label = pr_dhl_checkout_frontend.packstation;
            break;
          case 'servicepoint':
            gmap_marker_icon = pr_dhl_checkout_frontend.parcelshop_icon;
            shop_name = pr_dhl_checkout_frontend.parcelShop;
            shop_label = pr_dhl_checkout_frontend.branch;
            break;
          case 'postoffice':
            gmap_marker_icon = pr_dhl_checkout_frontend.post_office_icon;
            shop_name = pr_dhl_checkout_frontend.postoffice;
            shop_label = pr_dhl_checkout_frontend.branch;
            break;
          case 'postbank':
            gmap_marker_icon = pr_dhl_checkout_frontend.post_office_icon;
            shop_name = pr_dhl_checkout_frontend.postoffice;
            shop_label = pr_dhl_checkout_frontend.branch;
            break;
          default:
            gmap_marker_icon = pr_dhl_checkout_frontend.packstation_icon;
            shop_name = pr_dhl_checkout_frontend.packstation;
            shop_label = pr_dhl_checkout_frontend.packstation;
            break;
        }

        shop_name += ' ' + value.location.keywordId;

        var contentString = '<div id="parcel-content">'+
                            '<div id="site-notice">'+
                            '</div>'+
                            '<h4 class="parcel-title">' + shop_name + '</h4>'+
                            '<div id="bodyContent">'+
                            '<div>' + value.place.address.streetAddress + '</br>' + value.place.address.addressLocality + ' ' + value.place.address.postalCode + '</div>'+
                            openingTimes +
                            shopServices +
                            '<button type="button" class="parcelshop-select-btn" id="' + value.location.ids[0].locationId + '">' + pr_dhl_checkout_frontend.select + '</button>'+
                            '</div>'+
                            '</div>';

        // Create a Leaflet marker
        var marker = L.marker(uluru, {
          icon: L.icon({
            iconUrl: gmap_marker_icon,
          }),
          title: shop_label,
        }).addTo(map);

        // Create a popup
        marker.bindPopup(contentString, { maxWidth: 300 });

        // Add click event listener
        marker.on('click', function() {
          clearOverlays();
          marker.openPopup();
        });

        // Add to the info window array
        infoWinArray.push(marker);

       
      });

      // Clear all popups
      function clearOverlays() {
        for (var i = 0; i < infoWinArray.length; i++) {
          infoWinArray[i].closePopup();
        }
      }
     
    },
    selectedShop: function() {

      var parcelShopId = $(this).attr('id');

      $.each(wc_checkout_dhl_parcelfinder.parcelShops, function(key,value) {

        if( value.location.ids[0].locationId == parcelShopId ) {
          switch (value.location.type) {
            case 'locker':
              var shop_name = pr_dhl_checkout_frontend.packstation;
              $('.woocommerce-checkout #shipping_dhl_address_type').val('dhl_packstation').trigger('change');
              break;
            case 'servicepoint':
              var shop_name = pr_dhl_checkout_frontend.parcelShop;
              $('.woocommerce-checkout #shipping_dhl_address_type').val('dhl_branch').trigger('change');
              break;
            case 'postoffice':
              var shop_name = pr_dhl_checkout_frontend.postoffice;
              $('.woocommerce-checkout #shipping_dhl_address_type').val('dhl_branch').trigger('change');
              break;
            case 'postbank':
              var shop_name = pr_dhl_checkout_frontend.postoffice;
              $('.woocommerce-checkout #shipping_dhl_address_type').val('dhl_branch').trigger('change');
              break;
            default:
              var shop_name = pr_dhl_checkout_frontend.packstation;
              $('.woocommerce-checkout #shipping_dhl_address_type').val('dhl_packstation').trigger('change');
              break;
          }

          $('.woocommerce-checkout #shipping_first_name').val( $('.woocommerce-checkout #billing_first_name').val() );
          $('.woocommerce-checkout #shipping_last_name').val( $('.woocommerce-checkout #billing_last_name').val() );
          // $('.woocommerce-checkout #shipping_company').val( '' );
          $('.woocommerce-checkout #shipping_address_1').val( shop_name + ' ' + value.location.keywordId );
          $('.woocommerce-checkout #shipping_address_2').val( '' );
          $('.woocommerce-checkout #shipping_postcode').val( value.place.address.postalCode );
          $('.woocommerce-checkout #shipping_city').val( value.place.address.addressLocality );

          $.fancybox.close();
        }
      });
    }
  };

  if ( jQuery("[data-fancybox]").length > 0 ) {

    jQuery("[data-fancybox]").fancybox({
      modal: true
    });

  }

  wc_checkout_dhl_parcelfinder.init();

});
