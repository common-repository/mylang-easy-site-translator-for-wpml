(function ($) {
   $.fn.progress = function () {
      var percent = this.data("percent");
      var cancelled = this.data("cancelled");
      var width;
      if (cancelled) {
         $(".bar-two .progress-bar").addClass("notransition");
         //  console.log("Canceelled")
         width = "100%";
      } else {
         width = percent;
      }

      this.css("width", width);
   };
}(jQuery));

/**
 * Create and show a dismissible admin notice
 */
function mylang_add_admin_notice(msg, msg_class) {

   /* create notice div */

   var div = document.createElement('div');
   div.classList.add('notice', msg_class);

   /* create paragraph element to hold message */

   var p = document.createElement('p');

   /* Add message text */

   p.appendChild(document.createTextNode(msg));

   // Optionally add a link here

   /* Add the whole message to notice div */

   div.appendChild(p);

   /* Create Dismiss icon */

   var b = document.createElement('button');
   b.setAttribute('type', 'button');
   b.classList.add('notice-dismiss');

   /* Add screen reader text to Dismiss icon */

   var bSpan = document.createElement('span');
   bSpan.classList.add('screen-reader-text');
   bSpan.appendChild(document.createTextNode('Dismiss this notice'));
   b.appendChild(bSpan);

   /* Add Dismiss icon to notice */

   div.appendChild(b);

   /* Insert notice after the first h1 */

   var h3 = document.getElementById('div_notice');
   h3.appendChild(div);


   /* Make the notice dismissable when the Dismiss icon is clicked */

   b.addEventListener('click', function () {
      div.parentNode.removeChild(div);
   });


}

function toggleSourceLanguage(language_code) {
   console.log(language_code);
   if (jQuery("#srclang-" + language_code).is(':selected')) {
      jQuery("#targetlang-" + language_code).prop('disabled', true);
   }

}

jQuery(document).ready(function () {

   if (jQuery('.translate_item').is(":checked") && jQuery('.target_language').is(":checked")) {
      jQuery('#translate').prop('disabled', false);
      jQuery('#calculation_translate').prop('disabled', false);
   } else {
      jQuery('#translate').prop('disabled', true);
      jQuery('#calculation_translate').prop('disabled', true);
   }

   jQuery(".translate_item").on("change", function () {
      if (jQuery('.translate_item').is(":checked") && jQuery('.target_language').is(":checked")) {
         jQuery('#translate').prop('disabled', false);
         jQuery('#calculation_translate').prop('disabled', false);
      } else {
         jQuery('#translate').prop('disabled', true);
         jQuery('#calculation_translate').prop('disabled', true);
      }
   });

   jQuery(".target_language").on("change", function () {
      if (jQuery('.translate_item').is(":checked") && jQuery('.target_language').is(":checked")) {
         jQuery('#translate').prop('disabled', false);
         jQuery('#calculation_translate').prop('disabled', false);
      } else {
         jQuery('#translate').prop('disabled', true);
         jQuery('#calculation_translate').prop('disabled', true);
      }
   });

   jQuery("#srclang-ref").on('change', function () {
      //
      var language_code = this.value;
      var language_code_mb = jQuery("#srclang-ref_mb").val();
      // var targetPreviouslyChecked = jQuery("#targetlang-"+language_code).is(':checked')
      // jQuery("#targetlang-"+language_code).prop('disabled', true)
      var targetElement = "#targetlang-" + language_code;
      var targetElement_mb = "#targetlang-" + language_code_mb;

      jQuery('.target_language').not(targetElement || targetElement_mb).prop('disabled', false);

      jQuery(targetElement).prop('disabled', function (_, val) { return !val; });
      jQuery(targetElement_mb).prop('disabled', function (_, val) { return !val; });

      if (jQuery(targetElement).is(':checked')) {
         jQuery(targetElement).prop("checked", false);
      }

   });

   jQuery("#srclang-ref_mb").on('change', function () {
      //
      var language_code = this.value;
      var language_code_mass = jQuery("#srclang-ref").val();
      // var targetPreviouslyChecked = jQuery("#targetlang-"+language_code).is(':checked')
      // jQuery("#targetlang-"+language_code).prop('disabled', true)
      var targetElement = "#targetlang-" + language_code;
      var targetElement_mass = "#targetlang-" + language_code_mass;

      jQuery('.target_language').not(targetElement || targetElement_mass).prop('disabled', false);

      jQuery(targetElement).prop('disabled', function (_, val) { return !val; });
      jQuery(targetElement_mass).prop('disabled', function (_, val) { return !val; });

      if (jQuery(targetElement).is(':checked')) {
         jQuery(targetElement).prop("checked", false);
      }

   });


   var progressBar = jQuery(".bar-two .progress-bar").progress();

   var continue_translation = jQuery("#translate").data('continue-translation');

   var storeTimeInterval;

   var autoRefresh;

   jQuery('#mylang-cancel').attr('disabled', 'disabled');

   jQuery("#translate").click(function (e) {
      e.preventDefault();
      jQuery('.bar-two .progress-bar').css("width", '0%').text('0%');
      jQuery(".bar-two .progress-bar").addClass("notransition");
      // items for translate 
      var data_translate_item = {};
      jQuery('.translate_item:checked').each(function (index, elem) {

         var str = jQuery(elem).attr('name');
         var newstr = str.replace('mylang_settings', '').split('][');
         if (typeof data_translate_item[newstr[0].replace('[', '')] === 'undefined') {
            data_translate_item[newstr[0].replace('[', '')] = {};
         }

         data_translate_item[newstr[0].replace('[', '')][newstr[1].replace(']', '')] = {};
         data_translate_item[newstr[0].replace('[', '')][newstr[1].replace(']', '')] = jQuery(elem).val();

      });

      // language
      var data_translate_language = {};
      jQuery('.target_language:checked').each(function (index, elem) {

         var str = jQuery(elem).attr('name');
         var newstr = str.replace('mylang_settings', '').split('][');
         if (typeof data_translate_language[newstr[0].replace('[', '')] === 'undefined') {
            data_translate_language[newstr[0].replace('[', '')] = {};
         }

         data_translate_language[newstr[0].replace('[', '')][newstr[1].replace(']', '')] = {};
         data_translate_language[newstr[0].replace('[', '')][newstr[1].replace(']', '')] = jQuery(elem).val();

      });

      // Source Language
      var data_source_language = '';

      data_source_language = jQuery('#srclang-ref option:selected').val();
      // Update Mode
      var data_update_mode = '';
      data_update_mode = jQuery('#mylang_update_mode option:selected').val();

      // API Token
      var data_API_token = '';
      data_API_token = jQuery('#mylang_api_token').val();

      // ajax 

      jQuery.ajax({
         type: "post",
         dataType: "json",
         beforeSend: function () {
            jQuery('#translate').attr('disabled', true).text('Translating...');
         },
         complete: function () {
            jQuery('#translate').attr('disabled', true);
         },
         url: ajax_object.ajax_url,
         data: {
            action: "mylang_translate_action",
            "data_translate_item": data_translate_item,
            "data_translate_language": data_translate_language,
            "data_source_language": data_source_language,
            "data_update_mode": data_update_mode,
            "data_API_token": data_API_token
         },
         success: function (response) {
            console.log(response);
         },
         error: function (xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
         }
      }).done(function (data) {
         console.log(data);

         if (data['error']) {
            console.log("Translation cancelled due to error...");
            jQuery('#div_notice').prepend('<div class="notice notice-error is-dismissible"><p>' + data['error'] + '</p></div>');
            jQuery('.bar-two .progress-bar').css({ "width": '100%', 'background-color': 'red' }).text(data['error']);
            return false;
         }
         console.log("Start translation...");
         read_log_file();
         mylang_translate();

      });

      //start timer
      check_progress();
   });

   if (continue_translation) {
      jQuery('#mylang-cancel').attr('disabled', false);
      console.log("Continue translation...");
      mylang_translate();
      //start timer
      check_progress();
   }

   function mylang_translate() {
      jQuery('#mylang-cancel').attr('disabled', false);
      jQuery.ajax({
         type: "post",
         dataType: "json",
         url: ajax_object.ajax_url,
         data: { action: "mylang_translate_one_item_action" },
         success: function (json) {
            if (json['complete']) {
               var message = 'The work is done. Select the option "Re-translate all items" if you want to translate again';
               mylang_add_admin_notice(message, 'notice-success');
            }
            if (json['error']) {
               // mylang_add_admin_notice(json['error'],'notice-error')
               jQuery('#div_notice').prepend('<div class="notice notice-error is-dismissible"><p>' + json['error'] + '</p></div>');
               jQuery('.bar-two .progress-bar').css({ "width": '100%', 'background-color': 'red' }).text(json['error']);
            }
            if (json['translate']) {
               mylang_translate();
            }
         },
         error: function (xhr, ajaxOptions, thrownError) {
            alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
         }
      });
   }

   function check_progress() {
      jQuery('#translate').attr('disabled', true);
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
                  console.log("Stop Progress check");
                  jQuery('#translate').text('Translate my site').attr('disabled', false);
                  clearInterval(storeTimeInterval);
                  //do read logs
                  read_log_file();
                  console.log("Stopping log reading...");
                  // clearInterval(autoRefresh);
               }
               if (response['progress']) {
                  jQuery(".bar-two .progress-bar").removeClass("notransition");
                  jQuery('.bar-two .progress-bar').css("width", response['progress']).text(response['progress']);
               }
            }
         });
      }, 5000);

      console.log(stopTimer);
   }

   function read_log_file(start = true, refresh = true) {
      console.log("Reading logs......");

      if (start) {
         var data = {};
         data.action = "mylang_read_file_logs";

         if (refresh) {
            data.do = "refresh";
         }

         jQuery.ajax({
            type: "get",
            dataType: "json",
            url: ajax_object.ajax_url,
            data: data,
            success: function (json) {
               jQuery("#error-log").val('');
               console.log(json);
               jQuery("#error-log").val(function () {
                  return this.value + reverseString(json['log']).replace(/\n/, "");
               });
            },
            error: function (xhr, ajaxOptions, thrownError) {
               alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
            }
         });
      }
   }

   jQuery('body').on('click', '#calculation_translate', function (e) {

      jQuery(this).html("Calculating...");
      jQuery(this).attr('disabled', 'disabled');

      // items for translate 
      var data_translate_item = {};
      jQuery('.translate_item:checked').each(function (index, elem) {

         var str = jQuery(elem).attr('name');
         var newstr = str.replace('mylang_settings', '').split('][');
         if (typeof data_translate_item[newstr[0].replace('[', '')] === 'undefined') {
            data_translate_item[newstr[0].replace('[', '')] = {};
         }

         data_translate_item[newstr[0].replace('[', '')][newstr[1].replace(']', '')] = {};
         data_translate_item[newstr[0].replace('[', '')][newstr[1].replace(']', '')] = jQuery(elem).val();

      });

      // language
      var data_translate_language = {};
      jQuery('.target_language:checked').each(function (index, elem) {

         var str = jQuery(elem).attr('name');
         var newstr = str.replace('mylang_settings', '').split('][');
         if (typeof data_translate_language[newstr[0].replace('[', '')] === 'undefined') {
            data_translate_language[newstr[0].replace('[', '')] = {};
         }

         data_translate_language[newstr[0].replace('[', '')][newstr[1].replace(']', '')] = {};
         data_translate_language[newstr[0].replace('[', '')][newstr[1].replace(']', '')] = jQuery(elem).val();

      });

      // Source Language
      var data_source_language = '';
      var data_source_language_mb = '';

      data_source_language = jQuery('#srclang-ref option:selected').val();
      data_source_language_mb = jQuery('#srclangmb-ref option:selected').val();
      // Update Mode
      var data_update_mode = '';
      data_update_mode = jQuery('#mylang_update_mode option:selected').val();

      // API Token
      var data_API_token = '';
      data_API_token = jQuery('#mylang_api_token').val();


      // ajax 
      var button = jQuery(this);
      jQuery.ajax({
         type: "post",
         dataType: "json",
         url: ajax_object.ajax_url,
         data: {
            action: "calculation_translate",
            "data_translate_item": data_translate_item,
            "data_translate_language": data_translate_language,
            "data_source_language": data_source_language,
            "data_source_language_mb": data_source_language_mb,
            "data_update_mode": data_update_mode,
            "data_API_token": data_API_token
         },
         success: function (response) {
            button.removeAttr("disabled");
            button.html("Calculate");
            jQuery('#input_calculation_translate').html("<b>" + response + "</b> charters to translate");
            if (response == 0) {
               jQuery('#div_calculate').after("<div id='mylang-no-out' class='notice notice-warning fade is-dismissible'><p>It is possible that you have 0 characters to translate because you have selected the Empty for the Update mode.</p></div>");
            } else {
               jQuery('#mylang-no-out').remove();
            }

         }
      });

   });

});

window.addEventListener("load", function () {

   // store tabs variables
   var tabs = document.querySelectorAll("ul.nav-tabs > li");

   for (i = 0; i < tabs.length; i++) {
      tabs[i].addEventListener("click", switchTab);
   }

   function switchTab(event) {
      event.preventDefault();

      document.querySelector("ul.nav-tabs li.active").classList.remove("active");
      document.querySelector(".tab-pane.active").classList.remove("active");

      var clickedTab = event.currentTarget;
      var anchor = event.target;
      var activePaneID = anchor.getAttribute("href");

      clickedTab.classList.add("active");
      document.querySelector(activePaneID).classList.add("active");

   }

});

function reverseString(str) {
   return str.split("\n").reverse().join("\n");
}
