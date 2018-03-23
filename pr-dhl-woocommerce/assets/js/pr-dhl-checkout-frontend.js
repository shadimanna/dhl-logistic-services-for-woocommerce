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
