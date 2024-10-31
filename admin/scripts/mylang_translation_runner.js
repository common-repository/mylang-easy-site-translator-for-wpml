jQuery(document).ready(function () {
   mylang_translate();
   mylang_check_progress();
   var hasError = false;

   function mylang_translate() {
      jQuery.ajax({
         type: "post",
         dataType: "json",
         url: ajax_object.ajax_url,
         data: { action: "mylang_translate_one_item_action" },
         success: function (json) {
            if (json['complete']) {
               jQuery('#wpbody-content').prepend('<div class="notice notice-success is-dismissible"><p>All posts have been translated</p></div>');
            }
            if (json['error']) {
               jQuery('#wpbody-content').prepend('<div class="notice notice-error is-dismissible"><p>' + json['error'] + '</p></div>');
               jQuery('#mylang-translate-button').attr('disabled', false);
               jQuery('#mylang-magic-button > span').text('Error');
               jQuery("#loaderDiv").hide();
               jQuery('img#mylang-img').show();
               jQuery('#mylang-magic-button').parent().append('<div class="notice notice-error">' + json['error'] + '</div>');
               hasError = true;
            }
            if (json['translate']) {
               mylang_translate();
            }
         },
         error: function (xhr, ajaxOptions, thrownError) {
            //alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            jQuery('#mylang-magic-button > span').text("Error - Check logs");
            jQuery("#loaderDiv").hide();
            jQuery('img#mylang-img').show();
            hasError = true;
         }
      });
   }

   function mylang_check_progress() {
      var stopTimer = false;
      storeTimeInterval = setInterval(function () {
         console.log("Progress check");
         jqXHR = jQuery.ajax({
            type: "post",
            dataType: "json",
            url: ajax_object.ajax_url,
            data: { action: "mylang_check_progress_action" },
            success: function (response) {
               console.log(response);
               stopTimer = response['stop_check'];
               if (stopTimer) {
                  console.log("Stopping Progress check");
                  clearInterval(storeTimeInterval);
                  if (!hasError) {
                     //jQuery('#mylang-translate-button').attr('disabled', false);
                     jQuery('#mylang-magic-button > span').text('Translate');
                     jQuery("#loaderDiv").hide();
                     jQuery('img#mylang-img').show();
                     if (jQuery('#mylang-magic-button > span').text() == 'Translate' && jQuery('#mylang-magic-button').attr('disabled') == 'disabled') {
                        location.reload();
                     }
                  }
               }
            }
         });
      }, 5000);

      console.log(stopTimer);
   }

   if (jQuery('.translate_item').is(":checked")) {
      jQuery('#mylang-magic-button').prop('disabled', false);
   } else {
      jQuery('#mylang-magic-button').prop('disabled', true);
   }

   jQuery(".translate_item").on("change", function () {
      if (jQuery('.translate_item').is(":checked")) {
         jQuery('#mylang-magic-button').attr('disabled', false);
         console.log("Checked");
      } else {
         jQuery('#mylang-magic-button').attr('disabled', true);
         console.log("Unchecked");
      }
   });

   // Magic button translate
   jQuery("#mylang-magic-button").click(function (e) {
      e.preventDefault();
      // items for translate 
      var data_translate_item = {};
      var post_ID = jQuery('[name="post_ID"]').val() * 1;
      jQuery('.translate_item:checked').each(function (index, elem) {

         var str = jQuery(elem).attr('name');
         var newstr = str.replace('mylang_settings', '').split('][');
         if (typeof data_translate_item[newstr[0].replace('[', '')] === 'undefined') {
            data_translate_item[newstr[0].replace('[', '')] = {};
         }

         data_translate_item[newstr[0].replace('[', '')][newstr[1].replace(']', '')] = {};
         data_translate_item[newstr[0].replace('[', '')][newstr[1].replace(']', '')] = jQuery(elem).val();

      });

      jQuery.ajax({
         type: "post",
         dataType: "json",
         beforeSend: function () {
            jQuery('img#mylang-img').hide();
            jQuery("#loaderDiv").show();
            jQuery('#mylang-magic-button').attr('disabled', true);
            jQuery('#mylang-magic-button > span').text('Translating...');
         },
         complete: function () {
            jQuery('#mylang-magic-button').attr('disabled', true);
         },
         url: ajax_object.ajax_url,
         data: {
            action: "mylang_magic_btn_translate_action",
            "data_translate_item": data_translate_item,
            "post_ID": post_ID
         },
         success: function (response) {
            console.log(response);
         },
         error: function (xhr, ajaxOptions, thrownError) {
            //alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
         }
      }).done(function (data) {
         console.log(data);

         if (data['error']) {
            console.log("Translation cancelled due to error...");
            jQuery('#mylang-translate-button').attr('disabled', false);
            jQuery('#mylang-magic-button > span').text('Error');
            jQuery("#loaderDiv").hide();
            jQuery('img#mylang-img').show();
            jQuery('#mylang-magic-button').parent().append('<div class="notice notice-error">' + data['error'] + '</div>');
            hasError = true;
            return false;
         }
         console.log("Start translation...");
         // read_log_file();
         mylang_translate();
      });

      mylang_check_progress();
   });
});