<?php

class MyLangTranslate
{

    /**
     * The options name to be used in this plugin
     *
     * @since  	1.0.0
     * @access 	private
     * @var  	string 		$option_name 	Option name of this plugin
     */
    private $option_name = 'mylang_settings';

    /**
     * The plugin name to be used in this plugin
     *
     * @since  	1.0.0
     * @access 	private
     * @var  	string 		$plugin_name 	plugin name of this plugin
     */
    private $plugin_name = 'mylang';


    /**
     * The directory where logs are stored
     *
     * @since  	1.0.0
     * @access 	private
     * @var  	string 		$log_directory 	The directory where logs are stored
     */
    private $log_directory;

    /**
     * The logo path
     *
     * @since  	1.0.2
     * @access 	private
     * @var  	string 		$plugin_logo 	The logo path
     */
    private $plugin_logo;

    /**
     * Glossary array
     *
     * @since  	1.2.2
     * @access 	private
     * @var  	array 		$glossary     The glossary array
     */
    private $glossary;

    public function __construct()
    {
        global $wpdb;
        $glossary_table = $wpdb->prefix . "mylang_glossary";
        $this->glossary = $wpdb->get_results("SELECT * FROM $glossary_table", ARRAY_A);

        $upload_dir = wp_upload_dir();
        $this->log_directory = $upload_dir['basedir'];
        $this->plugin_logo = plugin_dir_url(MYLANG_PLUGIN_BASENAME) . '/mylang_logo.png';

        add_action('admin_init', array(&$this, 'mylang_register_plugin_settings'));

        //run ajax
        add_action('wp_ajax_mylang_translate_action',        array(&$this, 'mylang_btn_handle_translation'));
        add_action('wp_ajax_nopriv_mylang_translate_action', array(&$this, 'mylang_btn_handle_translation'));

        //magic buttton ajax
        add_action('wp_ajax_mylang_magic_btn_translate_action',        array(&$this, 'mylang_magic_btn_handle_translation'));
        add_action('wp_ajax_nopriv_mylang_magic_btn_translate_action', array(&$this, 'mylang_btn_handle_magic_translation'));

        //run ajax
        add_action('wp_ajax_calculation_translate',  array(&$this, 'calculation_translate'));

        //run glossary get_page ajax
        add_action('wp_ajax_get_page', array(&$this, 'get_page'));
        add_action('wp_ajax_nopriv_get_page', array(&$this, 'get_page'));

        //run glossary add_item ajax
        add_action('wp_ajax_add_item', array(&$this, 'add_item'));
        add_action('wp_ajax_nopriv_add_item', array(&$this, 'add_item'));

        //run glossary edit_item ajax
        add_action('wp_ajax_edit_item', array(&$this, 'edit_item'));
        add_action('wp_ajax_nopriv_edit_item', array(&$this, 'edit_item'));

        //run glossary remove_item ajax
        add_action('wp_ajax_remove_item', array(&$this, 'remove_item'));
        add_action('wp_ajax_nopriv_remove_item', array(&$this, 'remove_item'));

        //run ajax
        add_action('wp_ajax_mylang_translate_one_item_action', array(&$this, 'mylang_translate_single_queue_item'));
        add_action('wp_ajax_nopriv_mylang_translate_one_item_action', array(&$this, 'mylang_translate_single_queue_item'));

        //run ajax
        add_action('wp_ajax_mylang_read_file_logs', array(&$this, 'mylang_read_file_logs'));
        add_action('wp_ajax_nopriv_mylang_read_file_logs', array(&$this, 'mylang_read_file_logs'));


        add_action('wp_ajax_mylang_check_progress_action',        array(&$this, 'mylang_check_translation_progress'));
        add_action('wp_ajax_nopriv_mylang_check_progress_action', array(&$this, 'mylang_check_translation_progress'));

        add_action('admin_enqueue_scripts', array(&$this, 'mylang_enqueue_scripts'));
        add_action('wpml_admin_menu_configure', array(&$this, 'mylang_add_menu_to_wpml'));

        if (!$this->mylang_wpml_is_active()) {
            add_action('admin_menu',  array(&$this, 'plugin_setup_menu_without_wpml'));
        }

        add_action('add_meta_boxes',  array(&$this, 'mylang_add_metabox'), 10, 1);
    }

    public function mylang_add_metabox($post_type)
    {
        if ($post_type == 'post' || $post_type == 'page' || $post_type == 'product') {
            add_meta_box(
                'mylang_meta_box', // $id
                'myLang Magic Button', // $title
                array(&$this, 'mylang_meta_box_html'), // $callback
                $post_type, // $page
                'side', // $context
                'high' // $priority
            );

            // Code for placing the plugin after the Language block from WPML - Needs fixes
            // $user = wp_get_current_user();
            // $order = get_user_option("meta-box-order_" . $post_type, $user->ID);

            // if (is_array($order) && array_key_exists('side', $order)) {
            //     if (strpos($order['side'], "mylang_meta_box") !== false) {
            //         $order['side'] = str_replace('mylang_meta_box,', '', $order['side']);
            //     }

            //     if (strpos($order['side'], "icl_div") === false) {
            //         $order['side'] = 'icl_div,' . $order['side'];
            //     }

            //     if (substr($order['side'], -1) == ",") {
            //         $order['side'] = substr($order['side'], 0, -1);
            //     }

            //     $current_order = array();
            //     $current_order = explode(",", $order['side']);

            //     // Add this metabox to the order array
            //     $key = array_search('icl_div', $current_order, true);

            //     if ($key !== false) {
            //         $new_order = array_merge(
            //             array_slice($current_order, 0, $key + 1),
            //             array("mylang_meta_box")
            //         );

            //         if (count($current_order) > $key) {
            //             $new_order = array_merge(
            //                 $new_order,
            //                 array_slice($current_order, $key + 1)
            //             );
            //         }

            //         $order['side'] = implode(",", $new_order);

            //         update_user_option($user->ID, "meta-box-order_" . $post_type, $order, true);
            //     }
            // }
        }
    }

    public function mylang_meta_box_html($post)
    {
?>
        <style>
            .mylang-magic-button {
                -webkit-text-size-adjust: 100%;
                -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
                list-style: none;
                box-sizing: border-box;
                font: inherit;
                margin: 0;
                overflow: visible;
                text-transform: none;
                -webkit-appearance: button;
                font-family: inherit;
                display: inline-block;
                font-weight: normal;
                text-align: center;
                vertical-align: middle;
                touch-action: manipulation;
                cursor: pointer;
                border: 1px solid transparent;
                white-space: nowrap;
                font-size: 13px;
                line-height: 1.42857;
                border-radius: 3px;
                user-select: none;
                color: #666;
                border-color: #ddd;
                background: #e6e6e6;
                margin-bottom: 5px;
                padding: 5px 13px;
            }

            .mylang-magic-button:hover {
                color: #666;
                background-color: #e6e6e6;
                border-color: #bebebe;
            }

            .mylang-magic-button:hover,
            .mylang-magic-button:focus,
            .mylang-magic-button.focus {
                color: #666;
                text-decoration: none;
            }

            .mylang-magic-button.disabled,
            .mylang-magic-button[disabled],
            fieldset[disabled] .mylang-magic-button {
                cursor: not-allowed;
                opacity: 0.65;
                filter: alpha(opacity=65);
                -webkit-box-shadow: none;
                box-shadow: none;
            }

            .btn-default {
                color: #666;
                background-color: #fff;
                border-color: #ddd;
            }

            .tooltip {
                position: relative;
                display: inline-block;
            }

            .tooltip .tooltiptext {
                visibility: hidden;
                width: 120px;
                background-color: black;
                color: #fff;
                text-align: center;
                border-radius: 6px;
                padding: 5px;
                position: absolute;
                z-index: 1;
                bottom: 150%;
                left: 20%;
                margin-left: -60px;
            }

            .tooltip .tooltiptext::after {
                content: "";
                position: absolute;
                top: 100%;
                left: 50%;
                margin-left: -5px;
                border-width: 5px;
                border-style: solid;
                border-color: black transparent transparent transparent;
            }

            .tooltip:hover .tooltiptext {
                visibility: visible;
            }
        </style>
        <?php
        $post_type = get_post_type($post);
        $mylang_options = get_option($this->option_name);
        $mb_language_source = $mylang_options['mylang_source_language_magic_button'];

        // The current language of the post that is being edited
        $post_language = apply_filters( 'wpml_current_language', NULL );

        $checkboxes = $this->mylang_render_post_field();
        // $item_options = isset($options['mylang_post_magic_btn_translation_item']) ? $options['mylang_post_magic_btn_translation_item'] : array();

        if ($post_type == 'page') {
            $checkboxes = $this->mylang_render_page_items_field();
            // $item_options = isset($options['mylang_page_magic_btn_translation_item']) ? $options['mylang_page_magic_btn_translation_item'] : array();
        }

        if ($post_type == 'product') {
            $checkboxes = $this->mylang_render_product_items_field()['checkboxes'];
            // $item_options = isset($options['mylang_product_magic_btn_translation_item']) ? $options['mylang_product_magic_btn_translation_item'] : array();
        }

        foreach ($checkboxes as $setting => $label) {
            // $title_checked = isset($item_options[$setting]) ? $item_options[$setting] : 0;
        ?>
            <p>
                <input class="translate_item" type="checkbox" name="mylang_settings[mylan_magic_btn_translation_item][<?php echo esc_attr($setting) ?>]" value="1" checked /> <?php echo esc_html($label) ?>
            </p>
        <?php } ?>
        <div style="text-align: left;">
            <?php if ($mb_language_source != $post_language) { ?>
                <div style="margin-bottom: 10px;">Get translation from source language</div>
            <?php } else { ?>
                <div style="margin-bottom: 10px;">Translate to all other languages</div>
            <?php } ?>
            <div class="tooltip"><span class="dashicons dashicons-info"></span>
                <span class="tooltiptext">The language for the translation source should be selected in the extension settings</span>
            </div>
            <button type="button" id="mylang-magic-button" class="mylang-magic-button" data-field="stp-general">
                <!-- <i style="vertical-align: super; display:none;" class="fa fa-spinner fa-pulse" id="loaderDiv"></i> -->
                <img id="loaderDiv" style="display: none;" src="<?php echo site_url('wp-includes/images/spinner.gif');
                                                                ?>">
                <img id="mylang-img" width="23px" height="23px" src="<?php echo esc_url($this->plugin_logo); ?>">
                <span style="margin-left: 2px; vertical-align: super;">Translate</span>
            </button>
        </div>
    <?php
    }

    public function plugin_setup_menu_without_wpml()
    {
        $menu_label = '<img width="20" height="20" src="' . $this->plugin_logo . '"> myLang Easy Translator';
        add_menu_page($menu_label, $menu_label, 'manage_options', 'mylang', array(&$this, 'mylang_wpml_not_active_notice'));
    }

    public function mylang_add_menu_to_wpml()
    {
        //add menu to wpml
        $menu_label = '<img width="20" height="20" src="' . $this->plugin_logo . '"> myLang Easy Translator';

        $menu               = array();
        $menu['order']      = 80;
        $menu['page_title'] = $menu_label;
        $menu['menu_title'] = $menu_label;
        $menu['capability'] = 'manage_options';
        $menu['menu_slug']  = 'mylang';
        $menu['function']   = array($this, 'mylang_homepage');

        do_action('wpml_admin_menu_register_item', $menu);
    }
    public function mylang_settings_link_on_plugin_page($links)
    {
        // Build and escape the URL.
        $url = esc_url(add_query_arg(
            'page',
            'mylang&tab=settings',
            get_admin_url() . 'admin.php'
        ));
        // Create the link.
        $settings_link = "<a href='$url'>" . __('Settings') . '</a>';
        array_push(
            $links,
            $settings_link
        );

        // Build and escape the URL.
        $trans_url = esc_url(add_query_arg(
            'page',
            'mylang',
            get_admin_url() . 'admin.php'
        ));
        // Create the link.
        $translate_url = "<a href='$trans_url'>" . __('Translate') . '</a>';
        // Adds the link to the end of the array.
        array_push(
            $links,
            $translate_url
        );
        return $links;
    }

    public function mylang_enqueue_scripts()
    {

        $versions = md5(time());
        //TO DO
        if (isset($_GET['page']) && $_GET['page'] == 'mylang') {
            wp_enqueue_script('jquery-ui-progressbar');

            wp_enqueue_script('ajax-script', plugins_url('admin/scripts/script.js', MYLANG_PLUGIN_BASENAME), array('jquery'), $versions);

            wp_enqueue_style('font-awesome', plugins_url('admin/styles/font-awesome.min.css', MYLANG_PLUGIN_BASENAME));
            wp_enqueue_style('admin-styles', plugins_url('admin/styles/mylang_style.css', MYLANG_PLUGIN_BASENAME), array(), $versions);
        }

        if (!is_page('mylang')) {
            wp_enqueue_script('ajax-script', plugins_url('admin/scripts/mylang_translation_runner.js', MYLANG_PLUGIN_BASENAME), array('jquery'), $versions);

            // wp_enqueue_style('edit_styles', plugins_url('admin/styles/mylang_edit_style.css', MYLANG_PLUGIN_BASENAME), array(), $versions);
        }

        wp_localize_script('ajax-script', 'ajax_object', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    /**
     * https://wpml.org/wpml-hook/wpml_active_languages/
     */
    public function mylang_get_active_languages()
    {
        $languages = apply_filters('wpml_active_languages', NULL, 'orderby=id&order=desc');
        return $languages;
    }

    public function mylang_install_table()
    {
        global $wpdb;
        global $mylang_db_version;
        $mylang_db_version = '1.0';
        $table_name = $wpdb->prefix . "mylang_translate_queue";
        $glossary_table = $wpdb->prefix . "mylang_glossary";

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id mediumint(9) NOT NULL,
        cat_id mediumint(9),
        post_type varchar(55) DEFAULT '' NOT NULL,
        src_lang varchar(10) DEFAULT '' NOT NULL,
        target_lang varchar(10) DEFAULT '' NOT NULL,
        field_type varchar(55) DEFAULT '' NOT NULL,
        field_data text NOT NULL,
        field_data_translated text DEFAULT '' NOT NULL,
        status tinyint(2) DEFAULT '0' NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        $glossarySql = "CREATE TABLE $glossary_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        original text DEFAULT '' NOT NULL,
        translate text DEFAULT '' NOT NULL,
        PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta([$sql, $glossarySql]);
        add_option('mylang_db_version', $mylang_db_version);
    }

    public function mylang_uninstall_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . "mylang_translate_queue";
        $glossary_table = $wpdb->prefix . "mylang_glossary";
        $wpdb->query("DROP TABLE IF EXISTS $table_name;");
        $wpdb->query("DROP TABLE IF EXISTS $glossary_table;");
    }
    /**
     * Get posts depending on the post type and source language
     * @param string $post_type Post Type
     * @param string $src_language Post source language
     * @return mixed $results query results
     * */
    public function mylang_get_posts(string $post_type, string $src_language)
    {
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status'    => 'publish',
            'suppress_filters' => 0
        );
        global $sitepress;
        $current_lang = $sitepress->get_current_language();
        $sitepress->switch_lang($src_language);
        $results = get_posts($args);
        $sitepress->switch_lang($current_lang);
        return $results;
    }

    /**
     * Check if the translation of post_title, post_content, post_excerpt, _yoast_wpseo_metadesc and _yoast_wpseo_focuskw were deleted from
     * @param $master_post_id
     * @param $translated_post_id
     * @param $field_type
     * @return bool
     */
    public function mylang_check_if_translation_was_deleted_from_field($master_post_id, $translated_post_id, $field_type)
    {
        $master_post = get_post($master_post_id);
        $master_post_length = strlen($master_post->$field_type);

        $translated_post = get_post($translated_post_id);
        $translated_post_length = strlen($translated_post->$field_type);

        if (($translated_post_length * 3 < $master_post_length) || ($master_post_id == $translated_post_id)) {
            return true;
        }
        return false;
    }

    /**
     * Returns the table name
     * @return string the plugin table name
     */
    public function mylang_translation_table()
    {
        global $wpdb;
        return $wpdb->prefix . "mylang_translate_queue";
    }

    /**
     * select distict pair of source and target language from the table
     * loop through and count unique post id depending with the post type
     */
    public function mylang_log_translation_statistics()
    {
        $table_name = $this->mylang_translation_table();
        global $wpdb;
        $lang_pairs = $wpdb->get_results("SELECT src_lang,target_lang FROM $table_name GROUP BY src_lang,target_lang");
        $total = 0;
        // $i = 0;
        foreach ($lang_pairs as $pair) {
            $_src  = $pair->src_lang;
            $_target = $pair->target_lang;
            $log_text = strtoupper($_src . ' -> ' . $_target) . "\n";

            $post_count = $this->mylang_count_translated_posts('post', $_src, $_target);
            $product_count = $this->mylang_count_translated_posts('product', $_src, $_target);
            $page_count = $this->mylang_count_translated_posts('page', $_src, $_target);

            // $translated_posts = $this->mylang_get_translated_posts('post', $_src, $_target);
            // $translated_products = $this->mylang_get_translated_posts('product', $_src, $_target);
            // $translated_pages = $this->mylang_get_translated_posts('page', $_src, $_target);

            $log_text .= 'Posts translated: ' . $post_count . "\n";
            $total += $post_count;

            $log_text .= 'Products translated: ' . $product_count . "\n";
            $total += $product_count;

            $log_text .= 'Pages translated: ' . $page_count . "\n";
            $total += $page_count;

            mylang_translation_log($log_text);
            mylang_translation_log($log_text, 'a', 'mylang_detailed_translation_log');
        }

        $terminate_datetime = date('Y-m-d H:i:s');
        $total_chars = (int) $this->mylang_count_total_translated_characters();
        $log_text = 'End: ' . $terminate_datetime . ' server time' . "\n";
        $log_text .= 'Total units translated: ' . $total . "\n";
        $log_text .= "Total characters translated: " . number_format($total_chars, 0, '.', '.') . "\n";

        $start_time = get_option('mylang_last_start_datetime');
        $start_time = date("Y-m-d H:i:s", $start_time);

        $time_elapsed = $this->mylang_dateDifference($start_time, $terminate_datetime, '%h hours %i minutes %s seconds');
        $log_text .= 'Total time: ' . $time_elapsed . "\n";
        $log_text .= "Thanks for using myLang.\n\n\n";

        // - Total updated: 100
        mylang_translation_log($log_text);
        mylang_translation_log($log_text, 'a', 'mylang_detailed_translation_log');
        delete_option('mylang_last_start_datetime');
        //write to the log file
    }

    private function mylang_dateDifference($date_1, $date_2, $differenceFormat = '%a')
    {
        $datetime1 = date_create($date_1);
        $datetime2 = date_create($date_2);

        $interval = date_diff($datetime1, $datetime2);

        return $interval->format($differenceFormat);
    }

    /**
     * Returns the total number of translated characters
     */
    public function mylang_count_total_translated_characters()
    {
        $table_name = $this->mylang_translation_table();
        global $wpdb;
        //$total_translated_characters = $wpdb->get_var("SELECT SUM(CHAR_LENGTH(field_data)) FROM $table_name");
        $data = $wpdb->get_results("SELECT field_data FROM $table_name");
        $total_translated_characters = 0;
        foreach ($data as $row) {
            $total_translated_characters += strlen($row->field_data);
        }

        return $total_translated_characters;
    }

    /**
     * Returns the translated object ID(post_type or term) or original if missing
     *
     * @param string $post_type string The post type
     * @param string $src_lang The source language of the the post
     * @param string $target_lang The target language of the the post
     * @param integer $status The translation status
     * @return mixed the count of the posts
     */
    public function mylang_count_translated_posts($post_type, $src_lang, $target_lang, $status = 1)
    {
        $table_name = $this->mylang_translation_table();
        //select distict pair of src and target language
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(DISTINCT(post_id)) FROM $table_name WHERE src_lang = '" . $src_lang . "' AND target_lang = '" . $target_lang . "' AND post_type = '" . $post_type . "' AND status = '" . $status . "' AND CHAR_LENGTH(field_data) > 0 ");
    }

    /**
     * Returns the translated results
     *
     * @param string $post_type string The post type
     * @param string $src_lang The source language of the the post
     * @param string $target_lang The target language of the the post
     * @param integer $status The translation status
     * @return mixed the count of the posts
     */
    public function mylang_get_translated_posts($post_type, $src_lang, $target_lang, $status = 1)
    {
        $table_name = $this->mylang_translation_table();
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM $table_name WHERE src_lang = '" . $src_lang . "' AND target_lang = '" . $target_lang . "' AND post_type = '" . $post_type . "' AND status = '" . $status . "' AND CHAR_LENGTH(field_data) > 0 ");
    }

    /**
     * Get first 5 items from queue where status is zero (0)
     */
    public function mylang_get_queue_items($limit = 10)
    {
        global $wpdb;
        $table_name = $this->mylang_translation_table();
        $sql = "SELECT * FROM " . $table_name . "";
        $sql .= " WHERE status = '0' AND CHAR_LENGTH(field_data) > 0 ";
        $sql .= " ORDER BY ID ASC LIMIT $limit";

        $results = $wpdb->get_results($sql);

        return $results;
    }

    public function mylang_wp_update_post($post_id, $field_type, $content, $to, $cat_id)
    {
        if ($field_type == 'product_cat' || $field_type == 'category') {
            $this->mylang_update_category($post_id, $field_type, $content, $to, $cat_id);
        } else {
            $_update = array(
                'ID' => $post_id,
                $field_type   => $content
            );
            $new_id = wp_update_post($_update);

            if ($field_type == '_yoast_wpseo_focuskw' || $field_type == '_yoast_wpseo_metadesc') {
                update_post_meta($new_id, $field_type, $content);
            }
        }
    }

    private function mylang_update_category($post_id, $field_type, $content, $to, $cat_id)
    {
        $is_translated = apply_filters('wpml_element_has_translations', NULL, $cat_id, $field_type, $to);
        $translated_object_id = apply_filters('wpml_object_id', $cat_id, $field_type, true, $to);

        if (!$is_translated || (int)$translated_object_id == (int)$cat_id) {
            $new_cat_id = $this->mylang_duplicate_category($cat_id, $field_type, $to);
            $translated_object_id = apply_filters('wpml_object_id', $cat_id, $field_type, true, $to);
        }

        $updated_term_fields = array(
            'name' => $content,
            'slug' => strtolower(sanitize_title($content)),
        );

        $updated_term = wp_update_term($translated_object_id, $field_type, $updated_term_fields);
        if (is_wp_error($updated_term)) {
            mylang_translation_log('Error updating term: ' . $updated_term->get_error_message(), 'a', 'mylang_translation_log');
        }
        wp_set_post_terms($post_id, $translated_object_id, $field_type, false);
        // mylang_translation_log('Update Post : ' . $post_id . ' field: ' . $field_type . ' text: ' . $content . ' to: ' . $to . ' cat_id: ' . $cat_id . ' translated_object_id: ' . $translated_object_id);
    }

    public function mylang_translate_single_queue_item()
    {
        $json = array();
        // $start = 0;
        //check if mylang_queue_cancelled == 10
        $is_cancelled = get_option('mylang_queue_cancelled', '9');
        if ($is_cancelled != '10') {
            //get one item that is yet to be translated
            $queue_item = $this->mylang_get_queue_items(1);
            //translate it,
            $queue_item = !empty($queue_item) ? $queue_item[0] : array();
            if ($queue_item) {
                // mylang_translation_log('Translating Queue Item : ' . print_r($queue_item, true));

                $label = $this->my_mylang_render_field_label_by_type($queue_item->post_type, $queue_item->field_type);
                $payload = $this->mylang_translation_payload($queue_item->field_data, $queue_item->src_lang, $queue_item->target_lang);
                $lngTxt = strtoupper($queue_item->src_lang . ' -> ' .  $queue_item->target_lang) . "\n";
                mylang_translation_log("\n $lngTxt $queue_item->post_type = $queue_item->post_id, $label \n $queue_item->field_data", 'a', 'mylang_detailed_translation_log');
                $translated = $this->mylang_handle_api_reponse($payload);
                mylang_translation_log(" => \n $translated", 'a', 'mylang_detailed_translation_log');

                $post_id = $queue_item->post_id;
                //  mylang_translation_log($post_id . " : ". $queue_item->src_lang." > ".$queue_item->target_lang);
                if (isset($translated['error']) || isset($translated['issue'])) {
                    mylang_translation_log('Post ID: ' . $post_id);
                    mylang_translation_log("Translation: " . strtoupper($queue_item->src_lang . " -> " . $queue_item->target_lang));
                    mylang_translation_log("Data Translated: " . $queue_item->field_data);
                    mylang_translation_log("Data Type: " . $queue_item->field_type . "/n");

                    // Detailed log
                    mylang_translation_log('Post ID: ' . $post_id, 'a', 'mylang_detailed_translation_log');
                    mylang_translation_log("Translation: " . strtoupper($queue_item->src_lang . " -> " . $queue_item->target_lang), 'a', 'mylang_detailed_translation_log');
                    mylang_translation_log("Data Translated: " . $queue_item->field_data, 'a', 'mylang_detailed_translation_log');
                    mylang_translation_log("Data Type: " . $queue_item->field_type . "/n", 'a', 'mylang_detailed_translation_log');

                    $status_code = $translated['status_code'];

                    $_error_message = isset($translated['error']) ? $translated['error'] : $translated['message'];
                    $_error_message =  "Error : " . $status_code . ' - ' . $this->mylang_api_error_message($status_code);

                    mylang_translation_log($_error_message . "\n");
                    mylang_translation_log($_error_message . "\n", 'a', 'mylang_detailed_translation_log');
                    if (in_array($status_code, [401, 403, 404, 405, 456])) {
                        $json['error'] = $_error_message;
                        $json['translate'] = false;
                        update_option('mylang_queue_cancelled', '10');
                        $this->mylang_log_translation_statistics();
                        echo json_encode($json);
                        exit;
                    }
                }

                if (!empty($translated)) {
                    // mylang_translation_log('API response: '. $translated);
                    $this->mylang_wp_update_post($post_id, $queue_item->field_type, $translated, $queue_item->target_lang, $queue_item->cat_id);
                    $status = 1;
                } else {
                    //has Error
                    $translated = '';
                    $status = -1;
                }
                //update translation status in the queue
                $this->mylang_update_translation_item($translated, $queue_item->id, $status);
                $json['translate'] = true;
            } else {
                $json['translate'] = false;
                $json['complete'] = true;
            }
        } else {
            $json['translate'] = false;
            $json['message'] = "Translation cancelled!";
        }
        echo json_encode($json);
        exit;
    }

    /**
     * Cancel translation process
     * 10 = Cancelled
     * 9 =  Not cancelled
     * 
     */
    public function mylang_cancel_translation_queue()
    {
        update_option('mylang_queue_cancelled', '10');
    }

    /**
     * Update a translation item in the queue
     * @param string $text The content to update
     * @param int $id The ID of the item in the queue
     * @param int $status The update status
     */
    public function mylang_update_translation_item($text, $id, $status)
    {
        global $wpdb;
        $table_name = $this->mylang_translation_table();
        $wpdb->update(
            $table_name,
            array(
                'field_data_translated' => $text,   // string
                'status' => $status    // integer (number) 
            ),
            array('id' => $id),
            array(
                '%s',   // value1
                '%d'    // value2
            ),
            array('%d') //where ID = 
        );
    }

    /**
     * Count items in the queue
     */
    public function mylang_queue_count()
    {
        global $wpdb;
        $table_name = $this->mylang_translation_table();

        return $wpdb->get_var("SELECT COUNT(id) FROM $table_name WHERE CHAR_LENGTH(field_data) > 0 ");
    }

    public function mylang_complete_queue_count()
    {
        global $wpdb;
        $table_name = $this->mylang_translation_table();
        //$completed_count = count completed translations
        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT count(id) FROM $table_name WHERE status = %d ",
                1
            )
        );
    }

    public function mylang_posts_added_to_queue()
    {
        $sql = "SELECT COUNT(DISTINCT(post_id)) FROM `wp_mylang_translate_queue`";
        global $wpdb;
        $table_name = $this->mylang_translation_table();
        //$completed_count = count completed translations
        return $wpdb->get_var($sql);
    }

    /**
     * Check translation progress
     */
    public function mylang_check_translation_progress()
    {
        $json[] = array();
        $is_cancelled = get_option('mylang_queue_cancelled', '9');
        if ($is_cancelled != '10') {
            // count all translations
            $queue_count = $this->mylang_queue_count();
            $completed_count = $this->mylang_complete_queue_count();

            if ($completed_count == $queue_count) {
                //all  have been translated, stop the cron translate_cron_job
                $this->mylang_cancel_translation_queue();
                $json['stop_check']   = true;
                $this->mylang_log_translation_statistics();
            } else {
                $json['stop_check']   = false;
            }

            $json['completed_count'] = $completed_count;
            $json['cancelled'] = get_option('mylang_queue_cancelled');
            $json['queue_count'] = $queue_count;
            $json['progress'] =    ($completed_count > 0 && $queue_count > 0) ? floor(($completed_count / $queue_count) * 100) . '%' : '0%';
        } else {
            $json['stop_check']   = true;
        }
        echo json_encode($json);
        exit;
    }

    public function mylang_add_item_to_queue_table($data = array())
    {
        global $wpdb;
        $table_name = $this->mylang_translation_table();
        $insert_data = array(
            'post_id' => $data['post_id'],
            'post_type' => $data['post_type'],
            'field_type' => $data['field_type'],
            'field_data' => $data['field_data'],
            'src_lang' => $data['src_lang'],
            'target_lang' => $data['target_lang'],
            'cat_id' => $data['cat_id'],
        );

        $format = array('%d', '%s', '%s', '%s', '%s', '%s');

        if (isset($data['cat_id'])) {
            $insert_data['cat_id'] = $data['cat_id'];
            $format[] = '%d';
        }

        $wpdb->insert($table_name, $insert_data, $format);
    }

    /**
     * Get the categories that don't have translations
     */
    public function mylang_get_categories_to_translate($master_post_id, $post_type, $target_lang)
    {
        $categories_to_translate = array();

        if ($post_type === 'product') {
            $taxonomy = 'product_cat';
        } else if ($post_type === 'post') {
            $taxonomy = 'category';
        } else {
            return false;
        }

        $terms = wp_get_post_terms($master_post_id, $taxonomy, array('fields' => 'ids'));

        foreach ($terms as $term) {
            $name = get_term($term)->name;
            $is_translated = apply_filters('wpml_element_has_translations', NULL, $term, $taxonomy, $target_lang);
            $translated_object_id = apply_filters('wpml_object_id', $term, $taxonomy, true, $target_lang);
            // mylang_translation_log("Checking category translation: $name = " . print_r($term, $term). " is translated: " . $is_translated . " Tanslated object ID: " . $translated_object_id);
            if ($is_translated && $translated_object_id != $term) {
                continue;
            }
            $categories_to_translate[] = $term;
        }

        return $categories_to_translate;
    }

    public function mylang_magic_btn_handle_translation()
    {
        $detailed_log  = $this->log_directory . '/mylang_detailed_translation_log.log';
        wp_delete_file($detailed_log);
        //log time
        $start_datetime = date('Y-m-d H:i:s');
        update_option('mylang_last_start_datetime', strtotime($start_datetime));
        mylang_translation_log('------- Translation request -------');
        mylang_translation_log('------- Translation request -------', 'a', 'mylang_detailed_translation_log');

        mylang_translation_log('Start: ' . $start_datetime . ' server time');
        mylang_translation_log('Start: ' . $start_datetime . ' server time', 'a', 'mylang_detailed_translation_log');

        $json = array();

        $mylang_options = get_option($this->option_name);
        $mb_language_source = $mylang_options['mylang_source_language_magic_button'];
        //$target_langs = $mylang_options['mylang_translate_to'];

        // option from ajax
        if (isset($_POST['data_translate_item']['mylan_magic_btn_translation_item'])) {
            $options['mylan_magic_btn_translation_item'] =  $this->sanitize($_POST['data_translate_item']['mylan_magic_btn_translation_item']);
        }

        if (!isset($mylang_options['mylang_api_token'])) {
            $json['error'] = "Error: 401 - Unauthorized -- Your API key is wrong.";
            echo json_encode($json);
            exit;
        }

        if (!isset($mb_language_source) || $mb_language_source == '--Select--') {
            $json['error'] = "Error: Source language is not set.";
            echo json_encode($json);
            exit;
        }

        update_option('mylang_queue_cancelled', '9');

        $magic_btn_options = isset($options['mylan_magic_btn_translation_item']) ? $options['mylan_magic_btn_translation_item'] : array();
        $translate_title = isset($magic_btn_options['title']) ? true : false;
        $translate_desc  = isset($magic_btn_options['description']) ? true : false;
        $translate_excerpt = isset($magic_btn_options['excerpt']) ? true : false;
        $translate_meta_desc = isset($magic_btn_options['meta_desc']) ? true : false;
        $translate_meta_keyword = isset($magic_btn_options['meta_keyword']) ? true : false;
        $translate_category = isset($magic_btn_options['product_cat']) ? true : false;

        global $wpdb;
        $categories_added_to_be_translated = [];

        $table_name = $this->mylang_translation_table();

        //delete all from queue
        //remove it for retranslation
        $wpdb->query("TRUNCATE TABLE $table_name ");

        // the id of the current post - post with the magic button
        $target_post_id = sanitize_text_field($_POST['post_ID']);
        $my_post_language_details = apply_filters('wpml_post_language_details', NULL, $target_post_id);
        $mb_target_lang = $my_post_language_details['language_code'];
        $target_langs = [];
        $source_condition = $mb_language_source == $mb_target_lang;

        if ($source_condition) {
            $active_languages = $this->mylang_get_active_languages();
            foreach ($active_languages as $language) {
                if ($language['language_code'] != $mb_language_source) {
                    $target_langs[] = $language['language_code'];
                }
            }
        } else {
            $target_langs[] = $mb_target_lang;
        }

        // $switch_lang = new WPML_Temporary_Switch_Language($sitepress, $target_lang);
        // $is_new_term = !term_exists($name, $taxonomy);
        // $switch_lang->restore_lang();

        // get post by id
        $post_type = get_post_type($target_post_id);
        $source_post_id = apply_filters('wpml_object_id', $target_post_id, $post_type, true, $mb_language_source);
        //mylang_translation_log('Source post ID: ' . $source_post_id  . ' Target post ID: ' . $target_post_id);
        $post = get_post($source_post_id);

        foreach ($target_langs as $code) {
            //check $code is not src_lang
            // if ($code != $mb_language_source) {
            //check if translation exists for this target language
            $id_to_check = $source_post_id;
            $inserted_id = $target_post_id;
            if($source_condition) {
                $id_to_check = $target_post_id;
                $is_translated = apply_filters('wpml_element_has_translations', NULL, $id_to_check, $post_type, $code);
                $translated_object_id = apply_filters('wpml_object_id', $id_to_check, $post_type, true, $code);

                if (!$is_translated || (int)$translated_object_id == (int)$source_post_id) {
                    //duplicate post as no translation exists
                    // do_action('wpml_admin_make_post_duplicates', $target_post_id);
                    $this->mylang_duplicate_post($source_post_id, $code, $post_type);
                }

                $inserted_id = apply_filters('wpml_object_id', $source_post_id, $post_type, true, $code);
            }
            
            $categories_to_translate = $this->mylang_get_categories_to_translate($id_to_check, $post_type, $code);

            $target_lang = $code;
            //use original post contents
            $_title = $post->post_title;
            $_content = $post->post_content;
            $_excerpt = $post->post_excerpt;
            $_meta_desc = get_post_meta($id_to_check, '_yoast_wpseo_metadesc', true);
            $_meta_keyword = get_post_meta($id_to_check, '_yoast_wpseo_focuskw', true);

            if ($translate_title) {
                $this->mylang_add_item_to_queue_table(array(
                    'post_id' => $inserted_id,
                    'field_type' => 'post_title',
                    'field_data' => $_title,
                    'src_lang' => $mb_language_source,
                    'post_type' => $post_type,
                    'target_lang' => $target_lang
                ));
            }

            if ($translate_desc) {
                $this->mylang_add_item_to_queue_table(array(
                    'post_id' => $inserted_id,
                    'field_type' => 'post_content',
                    'field_data' => $_content,
                    'src_lang' => $mb_language_source,
                    'post_type' => $post_type,
                    'target_lang' => $target_lang
                ));
            }

            if ($translate_excerpt) {
                $this->mylang_add_item_to_queue_table(array(
                    'post_id' => $inserted_id,
                    'field_type' => 'post_excerpt',
                    'field_data' => $_excerpt,
                    'src_lang' => $mb_language_source,
                    'post_type' => $post_type,
                    'target_lang' => $target_lang
                ));
            }

            if ($translate_meta_desc) {
                $this->mylang_add_item_to_queue_table(array(
                    'post_id' => $inserted_id,
                    'field_type' => '_yoast_wpseo_metadesc',
                    'field_data' => $_meta_desc,
                    'src_lang' => $mb_language_source,
                    'post_type' => $post_type,
                    'target_lang' => $target_lang
                ));
            }

            if ($translate_meta_keyword) {
                $this->mylang_add_item_to_queue_table(array(
                    'post_id' => $inserted_id,
                    'field_type' => '_yoast_wpseo_focuskw',
                    'field_data' => $_meta_keyword,
                    'src_lang' => $mb_language_source,
                    'post_type' => $post_type,
                    'target_lang' => $target_lang
                ));
            }

            if ($translate_category && $post_type == 'product') {
                foreach ($categories_to_translate as $category_id) {
                    $name = get_term($category_id)->name;
                    if (!in_array($name, $categories_added_to_be_translated)) {
                        $this->mylang_add_item_to_queue_table(array(
                            'post_id' => $inserted_id,
                            'field_type' => 'product_cat',
                            'field_data' => $name,
                            'src_lang' => $mb_language_source,
                            'post_type' => 'product',
                            'target_lang' => $target_lang,
                            'cat_id' => $category_id
                        ));
                        $categories_added_to_be_translated[] = $name;
                    }
                }
            }

            if ($translate_category && $post_type == 'post') {
                foreach ($categories_to_translate as $category_id) {
                    $name = get_term($category_id)->name;
                    if (!in_array($name, $categories_added_to_be_translated)) {
                        $this->mylang_add_item_to_queue_table(array(
                            'post_id' => $inserted_id,
                            'field_type' => 'category',
                            'field_data' => $name,
                            'src_lang' => $mb_language_source,
                            'post_type' => 'post',
                            'target_lang' => $target_lang,
                            'cat_id' => $category_id
                        ));
                        $categories_added_to_be_translated[] = $name;
                    }
                }
            }
            // }
        }


        echo json_encode($json);
        exit;
    }

    /**
     * Get source language
     * Get post type
     * Get posts depending on post type and src language
     * Check if translation exists
     */
    public function mylang_btn_handle_translation()
    {
        $detailed_log  = $this->log_directory . '/mylang_detailed_translation_log.log';
        wp_delete_file($detailed_log);
        //log time
        $start_datetime = date('Y-m-d H:i:s');
        update_option('mylang_last_start_datetime', strtotime($start_datetime));
        mylang_translation_log('------- Translation request -------');
        mylang_translation_log('------- Translation request -------', 'a', 'mylang_detailed_translation_log');

        mylang_translation_log('Start: ' . $start_datetime . ' server time');
        mylang_translation_log('Start: ' . $start_datetime . ' server time', 'a', 'mylang_detailed_translation_log');

        //get source language
        $json = array();
        // $options = get_option($this->option_name);
        // option from ajax
        if (isset($_POST['data_translate_item']['mylang_post_translation_item'])) {
            $options['mylang_post_translation_item'] =  $this->sanitize($_POST['data_translate_item']['mylang_post_translation_item']);
        }

        if (isset($_POST['data_translate_item']['mylang_page_translation_item'])) {
            $options['mylang_page_translation_item'] =  $this->sanitize($_POST['data_translate_item']['mylang_page_translation_item']);
        }

        if (isset($_POST['data_translate_item']['mylang_product_translation_item'])) {
            $options['mylang_product_translation_item'] = $this->sanitize($_POST['data_translate_item']['mylang_product_translation_item']);
        }

        if (isset($_POST['data_translate_language']['mylang_translate_to'])) {
            $options['mylang_translate_to'] =  $this->sanitize($_POST['data_translate_language']['mylang_translate_to']);
        }

        $options['mylang_source_language'] = sanitize_text_field($_POST['data_source_language']);
        $options['mylang_update_mode'] = sanitize_text_field($_POST['data_update_mode']);
        $options['mylang_api_token'] = sanitize_text_field($_POST['data_API_token']);

        update_option($this->option_name, $options);
        $src_language = $options['mylang_source_language'];
        $langs = $options['mylang_translate_to'];

        if (!isset($options['mylang_api_token'])) {
            $json['error'] = "Error: 401 - Unauthorized -- Your API key is wrong.";
            echo json_encode($json);
            exit;
        }

        update_option('mylang_queue_cancelled', '9');

        $post_types = array('post', 'page');
        if (defined('WC_VERSION') && defined('WCML_VERSION')) {
            $post_types[]  = 'product';
        }

        $post_options = isset($options['mylang_post_translation_item']) ? $options['mylang_post_translation_item'] : array();
        $translate_post_title = isset($post_options['title']) ? true : false;
        $translate_post_desc = isset($post_options['description']) ? true : false;
        $translate_post_excerpt = isset($post_options['excerpt']) ? true : false;
        $translate_post_meta_desc = isset($post_options['meta_desc']) ? true : false;
        $translate_post_meta_keyword = isset($post_options['meta_keyword']) ? true : false;
        $translate_post_category = isset($post_options['category']) ? true : false;

        $page_options = isset($options['mylang_page_translation_item']) ? $options['mylang_page_translation_item'] : array();
        $translate_page_title = isset($page_options['title']) ? true : false;
        $translate_page_desc = isset($page_options['description']) ? true : false;
        $translate_page_excerpt = isset($page_options['excerpt']) ? true : false;
        $translate_page_meta_desc = isset($page_options['meta_desc']) ? true : false;
        $translate_page_meta_keyword = isset($page_options['meta_keyword']) ? true : false;

        $product_options = isset($options['mylang_product_translation_item']) ? $options['mylang_product_translation_item'] : array();
        $translate_pdct_title = isset($product_options['title']) ? true : false;
        $translate_pdct_desc  = isset($product_options['description']) ? true : false;
        $translate_pdct_excerpt = isset($product_options['excerpt']) ? true : false;
        $translate_pdct_meta_desc = isset($product_options['meta_desc']) ? true : false;
        $translate_pdct_meta_keyword = isset($product_options['meta_keyword']) ? true : false;
        $translate_pdct_category = isset($product_options['product_cat']) ? true : false;

        $json['post_types'] = count($post_types);
        $update_mode = $options['mylang_update_mode'];
        global $wpdb;
        $categories_added_to_be_translated = [];

        $table_name = $this->mylang_translation_table();

        //delete all from queue
        //remove it for retranslation
        $wpdb->query("TRUNCATE TABLE $table_name ");

        for ($index = 0; $index < count($post_types); $index++) {
            //get the posts
            $post_type = $post_types[$index];
            $posts = $this->mylang_get_posts($post_type, $src_language);
            $json[$post_type] = array();
            //add items to the translation_queue_table
            foreach ($posts as $post) {
                $master_post_id = $post->ID;
                //loop through the target languages
                foreach ($langs as $code => $checked) {
                    //check $code is not src_lang
                    if ($code != $src_language) {
                        //check if translation exists for this target language
                        $is_translated = apply_filters('wpml_element_has_translations', NULL, $master_post_id, $post_type, $code);
                        $translated_object_id = apply_filters('wpml_object_id', $master_post_id, $post_type, true, $code);
                        $title_was_deleted = $this->mylang_check_if_translation_was_deleted_from_field($master_post_id, $translated_object_id, 'post_title');
                        $content_was_deleted = $this->mylang_check_if_translation_was_deleted_from_field($master_post_id, $translated_object_id, 'post_content');
                        $excerpt_was_deleted = $this->mylang_check_if_translation_was_deleted_from_field($master_post_id, $translated_object_id, 'post_excerpt');
                        $meta_desc_was_deleted = $this->mylang_check_if_translation_was_deleted_from_field($master_post_id, $translated_object_id, '_yoast_wpseo_metadesc');
                        $meta_keyword_was_deleted = $this->mylang_check_if_translation_was_deleted_from_field($master_post_id, $translated_object_id, '_yoast_wpseo_focuskw');
                        $categories_to_translate = $this->mylang_get_categories_to_translate($master_post_id, $post_type, $code);
                        $categories_not_trnslated = (!empty($categories_to_translate)) ? true : false;
                        $any_field_was_deleted = $title_was_deleted || $content_was_deleted || $excerpt_was_deleted || $meta_desc_was_deleted || $meta_keyword_was_deleted || $categories_not_trnslated;

                        if ($is_translated && $update_mode == 'empty' && $translated_object_id != $master_post_id && $any_field_was_deleted == false) {
                            //the post is already translated in this language,check the next language
                            //don't add to queue as the translation already exists
                            //iterate the next item on the loop
                            $json['exists'][] = array($master_post_id, $code);
                            continue;
                        } else if (!$is_translated || (int)$translated_object_id == (int)$master_post_id) {
                            //duplicate post as no translation exists
                            // do_action('wpml_admin_make_post_duplicates', $master_post_id);
                            $this->mylang_duplicate_post($master_post_id, $code, $post_type);
                        }
                        //the magic
                        $translated_object_id = apply_filters('wpml_object_id', $master_post_id, $post_type, true, $code);

                        $target_lang = $code;
                        $post_id = $translated_object_id;
                        //use original post contents
                        $_title = $post->post_title;
                        $_content = $post->post_content;
                        $_excerpt = $post->post_excerpt;
                        $_meta_desc = get_post_meta($master_post_id, '_yoast_wpseo_metadesc', true);
                        $_meta_keyword = get_post_meta($master_post_id, '_yoast_wpseo_focuskw', true);

                        // Conditions
                        $title_cond = ($update_mode == 'empty') ? $title_was_deleted : true;
                        $content_cond = ($update_mode == 'empty') ? $content_was_deleted : true;
                        $excerpt_cond = ($update_mode == 'empty') ? $excerpt_was_deleted : true;
                        $meta_desc_cond = ($update_mode == 'empty') ? $meta_desc_was_deleted : true;
                        $meta_keyword_cond = ($update_mode == 'empty') ? $meta_keyword_was_deleted : true;
                        $categories_cond = ($update_mode == 'empty') ? $categories_not_trnslated : true;

                        if (
                            ((($post_type === 'product') && $translate_pdct_title)
                                || (($post_type === 'post') && $translate_post_title)
                                || (($post_type === 'page') && $translate_page_title))
                            && $title_cond
                        ) {
                            $this->mylang_add_item_to_queue_table(array(
                                'post_id' => $post_id,
                                'field_type' => 'post_title',
                                'field_data' => $_title,
                                'src_lang' => $src_language,
                                'post_type' => $post_type,
                                'target_lang' => $target_lang
                            ));
                        }

                        if (
                            ((($post_type === 'product') && $translate_pdct_desc)
                                || (($post_type === 'post') && $translate_post_desc)
                                || (($post_type === 'page') && $translate_page_desc))
                            && $content_cond
                        ) {
                            $this->mylang_add_item_to_queue_table(array(
                                'post_id' => $post_id,
                                'field_type' => 'post_content',
                                'field_data' => $_content,
                                'src_lang' => $src_language,
                                'post_type' => $post_type,
                                'target_lang' => $target_lang
                            ));
                        }

                        if (
                            !empty($_excerpt) &&
                            (
                                (($post_type === 'product') && $translate_pdct_excerpt) ||
                                (($post_type === 'page') && $translate_page_excerpt) ||
                                (($post_type === 'post') && $translate_post_excerpt))
                            && $excerpt_cond
                        ) {
                            $this->mylang_add_item_to_queue_table(array(
                                'post_id' => $post_id,
                                'field_type' => 'post_excerpt',
                                'field_data' => $_excerpt,
                                'src_lang' => $src_language,
                                'post_type' => $post_type,
                                'target_lang' => $target_lang
                            ));
                        }

                        if (
                            !empty($_meta_desc) &&
                            (
                                (($post_type === 'product') && $translate_pdct_meta_desc) ||
                                (($post_type === 'page') && $translate_page_meta_desc) ||
                                (($post_type === 'post') && $translate_post_meta_desc))
                            && $meta_desc_cond
                        ) {
                            $this->mylang_add_item_to_queue_table(array(
                                'post_id' => $post_id,
                                'field_type' => '_yoast_wpseo_metadesc',
                                'field_data' => $_meta_desc,
                                'src_lang' => $src_language,
                                'post_type' => $post_type,
                                'target_lang' => $target_lang
                            ));
                        }

                        if (
                            !empty($_meta_keyword) &&
                            (
                                (($post_type === 'product') && $translate_pdct_meta_keyword) ||
                                (($post_type === 'page') && $translate_page_meta_keyword) ||
                                (($post_type === 'post') && $translate_post_meta_keyword))
                            && $meta_keyword_cond
                        ) {
                            $this->mylang_add_item_to_queue_table(array(
                                'post_id' => $post_id,
                                'field_type' => '_yoast_wpseo_focuskw',
                                'field_data' => $_meta_keyword,
                                'src_lang' => $src_language,
                                'post_type' => $post_type,
                                'target_lang' => $target_lang
                            ));
                        }

                        if (
                            $categories_not_trnslated &&
                            (($post_type === 'product') && $translate_pdct_category)
                            && $categories_cond
                        ) {
                            foreach ($categories_to_translate as $category_id) {
                                $name = get_term($category_id)->name;
                                if (!in_array($name, $categories_added_to_be_translated)) {
                                    $this->mylang_add_item_to_queue_table(array(
                                        'post_id' => $post_id,
                                        'field_type' => 'product_cat',
                                        'field_data' => $name,
                                        'src_lang' => $src_language,
                                        'post_type' => 'product',
                                        'target_lang' => $target_lang,
                                        'cat_id' => $category_id
                                    ));
                                    $categories_added_to_be_translated[] = $name;
                                }
                            }
                        }

                        if (
                            $categories_not_trnslated &&
                            (($post_type === 'post') && $translate_post_category)
                            && $categories_cond
                        ) {
                            foreach ($categories_to_translate as $category_id) {
                                $name = get_term($category_id)->name;
                                if (!in_array($name, $categories_added_to_be_translated)) {
                                    $this->mylang_add_item_to_queue_table(array(
                                        'post_id' => $post_id,
                                        'field_type' => 'category',
                                        'field_data' => $name,
                                        'src_lang' => $src_language,
                                        'post_type' => 'post',
                                        'target_lang' => $target_lang,
                                        'cat_id' => $category_id
                                    ));
                                    $categories_added_to_be_translated[] = $name;
                                }
                            }
                        }
                    }
                }
            }
        }

        echo json_encode($json);
        exit;
    }


    public function  calculation_translate()
    {
        //get source language
        $json = array();
        // $options = get_option($this->option_name);
        // option from ajax

        if (isset($_POST['data_translate_item']['mylang_post_translation_item'])) {
            $options['mylang_post_translation_item'] =  $this->sanitize($_POST['data_translate_item']['mylang_post_translation_item']);
        }

        if (isset($_POST['data_translate_item']['mylang_page_translation_item'])) {
            $options['mylang_page_translation_item'] =  $this->sanitize($_POST['data_translate_item']['mylang_page_translation_item']);
        }

        if (isset($_POST['data_translate_item']['mylang_product_translation_item'])) {
            $options['mylang_product_translation_item'] = $this->sanitize($_POST['data_translate_item']['mylang_product_translation_item']);
        }

        if (isset($_POST['data_translate_language']['mylang_translate_to'])) {
            $options['mylang_translate_to'] =  $this->sanitize($_POST['data_translate_language']['mylang_translate_to']);
        }

        $options['mylang_source_language'] = sanitize_text_field($_POST['data_source_language']);
        $options['mylang_update_mode'] = sanitize_text_field($_POST['data_update_mode']);
        $options['mylang_api_token'] = sanitize_text_field($_POST['data_API_token']);

        $src_language = $options['mylang_source_language'];
        $langs = $options['mylang_translate_to'];

        if (!isset($options['mylang_api_token'])) {
            $json['error'] = "Error: 401 - Unauthorized -- Your API key is wrong.";
            echo json_encode($json);
            exit;
        }

        $post_types = array('post', 'page');
        if (defined('WC_VERSION') && defined('WCML_VERSION')) {
            $post_types[]  = 'product';
        }

        $post_options = isset($options['mylang_post_translation_item']) ? $options['mylang_post_translation_item'] : array();
        $translate_post_title = isset($post_options['title']) ? true : false;
        $translate_post_desc = isset($post_options['description']) ? true : false;
        $translate_post_excerpt = isset($post_options['excerpt']) ? true : false;
        $translate_post_meta_desc = isset($post_options['meta_desc']) ? true : false;
        $translate_post_meta_keyword = isset($post_options['meta_keyword']) ? true : false;
        $translate_post_category = isset($post_options['category']) ? true : false;

        $page_options = isset($options['mylang_page_translation_item']) ? $options['mylang_page_translation_item'] : array();
        $translate_page_title = isset($page_options['title']) ? true : false;
        $translate_page_desc = isset($page_options['description']) ? true : false;
        $translate_page_excerpt = isset($page_options['excerpt']) ? true : false;
        $translate_page_meta_desc = isset($page_options['meta_desc']) ? true : false;
        $translate_page_meta_keyword = isset($page_options['meta_keyword']) ? true : false;

        $product_options = isset($options['mylang_product_translation_item']) ? $options['mylang_product_translation_item'] : array();
        $translate_pdct_title = isset($product_options['title']) ? true : false;
        $translate_pdct_desc  = isset($product_options['description']) ? true : false;
        $translate_pdct_excerpt = isset($product_options['excerpt']) ? true : false;
        $translate_pdct_meta_desc = isset($product_options['meta_desc']) ? true : false;
        $translate_pdct_meta_keyword = isset($product_options['meta_keyword']) ? true : false;
        $translate_pdct_category = isset($product_options['product_cat']) ? true : false;

        $json['post_types'] = count($post_types);
        $update_mode = $options['mylang_update_mode'];
        $categories_added_to_be_translated = [];
        global $wpdb;

        $table_name = $this->mylang_translation_table();
        $count_letters = 0;
        //delete all from queue
        //remove it for retranslation
        // $wpdb->query("TRUNCATE TABLE $table_name ");

        for ($index = 0; $index < count($post_types); $index++) {
            //get the posts
            $post_type = $post_types[$index];
            $posts = $this->mylang_get_posts($post_type, $src_language);
            $json[$post_type] = array();
            //add items to the translation_queue_table
            foreach ($posts as $post) {

                $master_post_id = $post->ID;
                //loop through the target languages
                foreach ($langs as $code => $checked) {
                    //check $code is not src_lang
                    if ($code != $src_language) {
                        //check if translation exists for this target language
                        $is_translated = apply_filters('wpml_element_has_translations', NULL, $master_post_id, $post_type, $code);
                        $translated_object_id = apply_filters('wpml_object_id', $master_post_id, $post_type, true, $code);
                        //$diffrent_no_chars = $this->mylang_check_if_translation_was_deleted($master_post_id, $translated_object_id);
                        $title_was_deleted = $this->mylang_check_if_translation_was_deleted_from_field($master_post_id, $translated_object_id, 'post_title');
                        $content_was_deleted = $this->mylang_check_if_translation_was_deleted_from_field($master_post_id, $translated_object_id, 'post_content');
                        $excerpt_was_deleted = $this->mylang_check_if_translation_was_deleted_from_field($master_post_id, $translated_object_id, 'post_excerpt');
                        $meta_desc_was_deleted = $this->mylang_check_if_translation_was_deleted_from_field($master_post_id, $translated_object_id, '_yoast_wpseo_metadesc');
                        $meta_keyword_was_deleted = $this->mylang_check_if_translation_was_deleted_from_field($master_post_id, $translated_object_id, '_yoast_wpseo_focuskw');
                        $categories_to_translate = $this->mylang_get_categories_to_translate($master_post_id, $post_type, $code);
                        $categories_not_trnslated = (!empty($categories_to_translate)) ? true : false;
                        $any_field_was_deleted = $title_was_deleted || $content_was_deleted || $excerpt_was_deleted || $meta_desc_was_deleted || $meta_keyword_was_deleted || $categories_not_trnslated;

                        // here I also have to check if the translated page nr of characters is diffrent form the original
                        if ($is_translated && $update_mode == 'empty' && $translated_object_id != $master_post_id && $any_field_was_deleted == false) {
                            //the post is already translated in this language,check the next language
                            //don't add to queue as the translation already exists
                            //iterate the next item on the loop
                            $json['exists'][] = array($master_post_id, $code);
                            continue;
                        }

                        //the magic
                        $translated_object_id = apply_filters('wpml_object_id', $master_post_id, $post_type, true, $code);

                        $target_lang = $code;
                        $post_id = $translated_object_id;
                        //use original post contents
                        $_title = $post->post_title;
                        $_content = $post->post_content;
                        $_excerpt = $post->post_excerpt;
                        $_meta_desc = get_post_meta($master_post_id, '_yoast_wpseo_metadesc', true);
                        $_meta_keyword = get_post_meta($master_post_id, '_yoast_wpseo_focuskw', true);

                        // Conditions
                        $title_cond = ($update_mode == 'empty') ? $title_was_deleted : true;
                        $content_cond = ($update_mode == 'empty') ? $content_was_deleted : true;
                        $excerpt_cond = ($update_mode == 'empty') ? $excerpt_was_deleted : true;
                        $meta_desc_cond = ($update_mode == 'empty') ? $meta_desc_was_deleted : true;
                        $meta_keyword_cond = ($update_mode == 'empty') ? $meta_keyword_was_deleted : true;
                        $categories_cond = ($update_mode == 'empty') ? $categories_not_trnslated : true;

                        if (
                            ((($post_type === 'product') && $translate_pdct_title)
                                || (($post_type === 'post') && $translate_post_title)
                                || (($post_type === 'page') && $translate_page_title))
                            && $title_cond
                        ) {
                            $count_letters += strlen($_title);
                        }

                        if (
                            ((($post_type === 'product') && $translate_pdct_desc)
                                || (($post_type === 'post') && $translate_post_desc)
                                || (($post_type === 'page') && $translate_page_desc))
                            &&  $content_cond
                        ) {
                            $count_letters += strlen($_content);
                        }

                        if (
                            !empty($_excerpt) &&
                            (
                                (($post_type === 'product') && $translate_pdct_excerpt) ||
                                (($post_type === 'page') && $translate_page_excerpt) ||
                                (($post_type === 'post') && $translate_post_excerpt))
                            && $excerpt_cond
                        ) {

                            $count_letters += strlen($_excerpt);
                        }

                        if (
                            !empty($_meta_desc) &&
                            (
                                (($post_type === 'product') && $translate_pdct_meta_desc) ||
                                (($post_type === 'page') && $translate_page_meta_desc) ||
                                (($post_type === 'post') && $translate_post_meta_desc))
                            && $meta_desc_cond
                        ) {

                            $count_letters += strlen($_meta_desc);
                        }

                        if (
                            !empty($_meta_keyword) &&
                            (
                                (($post_type === 'product') && $translate_pdct_meta_keyword) ||
                                (($post_type === 'page') && $translate_page_meta_keyword) ||
                                (($post_type === 'post') && $translate_post_meta_keyword))
                            && $meta_keyword_cond
                        ) {
                            $count_letters += strlen($_meta_keyword);
                        }

                        if (
                            !empty($categories_to_translate) &&
                            (($post_type === 'product') && $translate_pdct_category)
                            && $categories_cond
                        ) {
                            foreach ($categories_to_translate as $category_id) {
                                $name = get_term($category_id)->name;
                                if (!in_array($name, $categories_added_to_be_translated)) {
                                    $count_letters += strlen($name);
                                    $categories_added_to_be_translated[] = $name;
                                }
                            }
                        }

                        if (
                            (($post_type === 'post') && $translate_post_category)
                            && $categories_cond
                        ) {
                            // mylang_translation_log("CATEEEEEEGOREIS TO TRANSLATE: " . print_r($categories_to_translate, true));
                            foreach ($categories_to_translate as $category_id) {
                                $name = get_term($category_id)->name;
                                if (!in_array($name, $categories_added_to_be_translated)) {
                                    $count_letters += strlen($name);
                                    $categories_added_to_be_translated[] = $name;
                                }
                            }
                        }
                    }
                }
            }
        }

        echo json_encode(number_format($count_letters, 0, '.', '.'));
        exit;
    }

    /**
     * A custom sanitization function that will take the incoming input, and sanitize
     * the input before handing it back to WordPress to save to the database.
     *
     * @param    array    $input        The input.
     * @return   array    $new_input    The sanitized input.
     */
    public function sanitize($input)
    {
        // Initialize the new array that will hold the sanitize values
        $new_input = array();

        // Loop through the input and sanitize each of the values
        foreach ($input as $key => $val) {
            $new_input[$key] = (isset($input[$key])) ? sanitize_text_field($val) : 0;
        }

        return $new_input;
    }

    /**
     * Translate Yoast SEO meta description, and focus keyword
     */
    public function mylang_translate_yoast_post_meta($post_id, $src_lang, $target_lang)
    {

        $__metas = array();

        $post_options = isset($options['mylang_post_translation_item']) ? $options['mylang_post_translation_item'] : array();
        $translate_post_meta_keyword = isset($post_options['title']) ? true : false;
        $translate_post_meta_desc = isset($post_options['meta_desc']) ? true : false;

        $product_options = isset($options['mylang_product_translation_item']) ? $options['mylang_product_translation_item'] : array();
        $translate_product_meta_keyword = isset($product_options['meta_keyword']) ? true : false;
        $translate_product_meta_desc = isset($product_options['meta_desc']) ? true : false;

        if ($translate_product_meta_keyword || $translate_post_meta_keyword) {
            $__metas[] = '_yoast_wpseo_focuskw';
        }

        if ($translate_product_meta_desc    || $translate_post_meta_desc) {
            $__metas[] = '_yoast_wpseo_metadesc';
        }

        for ($index = 0; $index < count($__metas); $index++) {
            $meta_val = get_post_meta($post_id, $__metas[$index], true);

            //send to api for translation
            $_payload = $this->mylang_translation_payload($meta_val, $src_lang, $target_lang);
            $_translated = $this->mylang_handle_api_reponse($_payload);
            update_post_meta($post_id, $__metas[$index], $_translated, $meta_val);

            //set it to translated
            update_post_meta($post_id, $__metas[$index] . 'mylang_translated_' . $target_lang, $_translated, $meta_val);
        }
    }

    /**
     * Clear translation logs
     */
    public function mylang_clear_log()
    {
        $file  = $this->log_directory . '/mylang_translation_log.log';
        wp_delete_file($file);
        $location = $_SERVER['HTTP_REFERER'];
        wp_safe_redirect($location);
        exit();
    }

    public function mylang_homepage()
    {

        $this->mylang_wpml_not_active_notice();

        if (isset($_GET['download']) && sanitize_text_field($_GET['download'])) {
            $this->mylang_download_log();
        }

        if (isset($_GET['download_debug']) && sanitize_text_field($_GET['download_debug'])) {
            $this->mylang_download_debug_info();
        }

        if (isset($_GET['cancel']) && sanitize_text_field($_GET['cancel'])) {
            $this->mylang_cancel_translation_queue();
            $this->mylang_log_translation_statistics();
            $location = $_SERVER['HTTP_REFERER'];
            wp_safe_redirect($location);
            exit();
        }

        if (isset($_GET['clear'])) {
            $this->mylang_clear_log();
        }

        $log = @file_get_contents($this->log_directory . '/mylang_translation_log.log', FILE_USE_INCLUDE_PATH, null);

        require_once MYLANG_PLUGIN_PATH . '/admin/homepage.php';
    }

    /**
     * Read file logs
     */
    public function mylang_read_file_logs()
    {
        $file = $this->log_directory . '/mylang_translation_log.log';
        $option_name = 'mylang_log_file_start';

        //TODO
        if (isset($_GET['do'])) {
            //start from zero
            $read_file = $this->mylang_logs_started_by_line($file);
            $text = $read_file[0];
            $current_line = $read_file[1];
            update_option($option_name, $current_line);
        } else {
            //start from the last line read
            $_start_line = (int)get_option($option_name);
            $read_file = $this->mylang_logs_started_by_line($file, $_start_line);
            $text = $read_file[0];
            $current_line = $read_file[1];
            update_option($option_name, $current_line);
        }

        $json['log'] = $text;
        $json['next_start_line'] = $current_line;
        echo json_encode($json);
        exit;
    }

    /**
     * Returns the translated object ID(post_type or term) or original if missing
     *
     * @param $object_id integer|string|array The ID/s of the objects to check and return
     * @param $type the object type: post, page, {custom post type name}, nav_menu, nav_menu_item, category, tag etc.
     * @return string or array of object ids
     */
    public function mylang_translate_object_id($object_id, $type)
    {
        $current_language = apply_filters('wpml_current_language', NULL);
        // $current_language = 'en';
        // if array
        if (is_array($object_id)) {
            $translated_object_ids = array();
            foreach ($object_id as $id) {
                $translated_object_ids[] = apply_filters('wpml_object_id', $id, $type, true, $current_language);
            }
            return $translated_object_ids;
        } elseif (is_string($object_id)) {
            // if string
            // check if we have a comma separated ID string
            $is_comma_separated = strpos($object_id, ",");

            if ($is_comma_separated !== FALSE) {
                // explode the comma to create an array of IDs
                $object_id     = explode(',', $object_id);

                $translated_object_ids = array();
                foreach ($object_id as $id) {
                    $translated_object_ids[] = apply_filters('wpml_object_id', $id, $type, true, $current_language);
                }

                // make sure the output is a comma separated string (the same way it came in!)
                return implode(',', $translated_object_ids);
            } else {
                // if we don't find a comma in the string then this is a single ID
                return apply_filters('wpml_object_id', intval($object_id), $type, true, $current_language);
            }
        } else {
            return apply_filters('wpml_object_id', $object_id, $type, true, $current_language);
        }
    }

    public function mylang_show_admin_error($message)
    {
        echo esc_html(
            '<div class="error settings-error notice is-dismissible">
                <p>' . esc_html__($message, "mylang") . '</p>
            </div>'
        );
    }

    public function mylang_handle_api_reponse($payload)
    {
        //$to = $payload['to'] === 'zh' ? 'zh-hans' : $payload['to'];
        $to = $this->map_myLang_to_wpml($payload['to']);
        $glossaryReplaces = $this->replaceGlossary($payload['text'], $to);
        $response = $this->mylang_api_translate($payload);

        if (!isset($response['success'])) {
            $response['issue'] = true;
            return $response;
        }

        foreach ($glossaryReplaces as $preg => $replace) {
            $response['translated'] = preg_replace($preg, $replace, $response['translated']);
        }

        return $response['translated'];
    }

    public function mylang_translation_payload(string $text, string $from, string $to)
    {
        return [
            'text' => $text,
            'from' => $this->mylang_get_language_code($from),
            'to' => $this->mylang_get_language_code($to),
        ];
    }

    /**
     * Send data to the external API for translation
     * @param array $data The payload to be translated
     */
    public function mylang_api_translate($data = array())
    {

        $options = get_option($this->option_name);

        $api_token = $options['mylang_api_token'];

        $url = 'https://api.mylang.me/translate';

        $json = json_encode($data);
        $response = wp_remote_post(
            $url,
            array(
                'method'      => 'POST',
                'timeout'     => 45,
                'blocking'    => true,
                'headers'     => array(
                    'X-Auth-Token'  => $api_token,
                    'Content-Type'  => 'application/json',
                    'Content-Length' => strlen($json)
                ),
                'body'        => $json
            )
        );

        if (is_wp_error($response)) {
            if ($response->get_error_code() == 28) {
                $json['error'] = "Error: 408 - Request Timeout -- The server took too long to respond. Try again later.";
                echo json_encode($json);
                exit;
            }

            throw new Exception($response->get_error_message());
        }

        // get body response while get not error

        $result = json_decode(wp_remote_retrieve_body($response), true);
        $result['status_code'] = wp_remote_retrieve_response_code($response);
        return $result;
    }

    /**
     * Format language code to fit myLang API specifications
     * @param string $language The language to be formatted
     * @return string Formatted language
     */
    public function mylang_get_language_code(string $language)
    {
        $languageCodes = array(
            'zh-hans' => 'zh',
            'zh-hant' => 'zh-tw',
            'he' => 'iw',
            'kk-KK' => 'kk'
        );

        // if (substr_count($language, '-') > 0) {

        //     if ($language === 'zh-hans') {
        //         # its simplified chinese, later we will also checK for Taiwan(ZH-TW)
        //         $language = 'zh';
        //     }

        // }

        if (array_key_exists($language, $languageCodes)) {
            $language = $languageCodes[$language];
        }
        return $language;
    }

    /**
     * Format language code to fit WPML specifications
     * @param string $language The language to be formatted
     * @return string Formatted language
     */
    public function map_myLang_to_wpml(string $language)
    {
        $languageCodes = array(
            'zh' => 'zh-hans',
            'zh-tw' => 'zh-hant',
            'iw' => 'he',
            'kk' => 'kk-KK'
        );

        if (array_key_exists($language, $languageCodes)) {
            $language = $languageCodes[$language];
        }
        return $language;
    }

    /**
     * Prepare log file download
     * @param string $filename The file name on the system
     */
    public function mylang_download_log($filename = 'mylang_detailed_translation_log.log')
    {

        // Fix PHP headers
        ob_start();
        // HTTP headers for downloads
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=$filename");
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: " . filesize($this->log_directory . '/' . $filename));
        while (ob_get_level()) {
            ob_end_clean();
            @readfile($this->log_directory . '/' . $filename);
        }
    }

    public function mylang_settings()
    {
        require_once MYLANG_PLUGIN_PATH . '/admin/settings.php';
    }

    /**
     * Register the setting parameters
     *
     * @since  	1.0.0
     * @access 	public
     */
    public function mylang_register_plugin_settings()
    {
        register_setting(
            'mylang_settings',
            $this->option_name,
            'mylang_validate_settings'
        );
        // Add a General section
        add_settings_section(
            $this->option_name . '_general',
            __('myLang Settings', 'mylang'),
            array($this, $this->option_name . '_general_section_callback'),
            $this->plugin_name
        );

        add_settings_field(
            'mylang_api_token',
            'myLang API Token',
            array($this, $this->plugin_name . '_render_api_token_field'),
            $this->plugin_name,
            $this->option_name . '_general'
        );

        add_settings_field(
            'mylang_source_language',
            'Language Source For the Mass Translate',
            array($this, $this->plugin_name . '_render_source_language_field'),
            $this->plugin_name,
            $this->option_name . '_general'
        );

        add_settings_field(
            'mylang_source_language_magic_button',
            'Language Source For Magic Button',
            array($this, $this->plugin_name . '_render_source_language_magic_button_field'),
            $this->plugin_name,
            $this->option_name . '_general'
        );

        add_settings_field(
            'mylang_translate_to',
            'Translate To',
            array($this, $this->plugin_name . '_render_translate_to_field'),
            $this->plugin_name,
            $this->option_name . '_general'
        );

        add_settings_field(
            'mylang_update_mode',
            'Update Mode',
            array($this, $this->plugin_name . '_render_update_mode_field'),
            $this->plugin_name,
            $this->option_name . '_general'
        );

        add_settings_field(
            'mylang_product_translation_item',
            'Products',
            array($this, $this->plugin_name . '_render_product_items_field'),
            $this->plugin_name,
            $this->option_name . '_general'
        );


        add_settings_field(
            'mylang_page_translation_item',
            'Page',
            array($this, $this->plugin_name . '_render_page_items_field'),
            $this->plugin_name,
            $this->option_name . '_general'
        );

        add_settings_field(
            'mylang_post_translation_item',
            'Post',
            array($this, $this->plugin_name . '_render_post_field'),
            $this->plugin_name,
            $this->option_name . '_general'
        );
    }

    public function mylang_render_api_token_field()
    {
        $options = get_option($this->option_name);
    ?>
        <input type="text" id='mylang_api_token' name="mylang_settings[mylang_api_token]" value="<?php echo esc_attr(isset($options['mylang_api_token']) ? $options['mylang_api_token'] : ''); ?>" style="width: 100%;" />
        <br>
        <p class='lead' style='background-color:#fff; width:fit-content; padding:10px;'>Translations work only through a connection to the myLang Me translator. To receive an API key, you
            need to Sign Up on the <a href='https://mylang.me' target='_blank'>mylang.me</a> website and create a key in your Dashboard. For new users, there
            are 5 thousand free symbols available for translation.</p>
        <?php
    }

    public function my_mylang_render_field_label_by_type($post_type, $field_name)
    {
        $labels =  array(
            'post_title' => 'Title',
            'post_content' => 'HTML Text',
            'post_excerpt'  => 'Excerpt',
            '_yoast_wpseo_metadesc' => 'Meta Description (Yoast SEO)',
            '_yoast_wpseo_focuskw' => 'Focus Keyphrase (Yoast SEO)',
            'category' => 'Category Name',
        );

        if ($post_type == 'page') {
            $labels =  array(
                'post_title' => 'Title',
                'post_content' => 'HTML Text',
                'post_excerpt'  => 'Excerpt',
                '_yoast_wpseo_metadesc' => 'Meta Description (Yoast SEO)',
                '_yoast_wpseo_focuskw' => 'Focus Keyphrase (Yoast SEO)',
            );
        }
        if ($post_type == 'product') {
            $labels =  array(
                'post_title' => 'Title (WooCommerce)',
                'post_content' => 'HTML Text (WooCommerce)',
                'post_excerpt'  => 'HTML Short Description (WooCommerce)',
                '_yoast_wpseo_metadesc' => 'Meta Description (Yoast SEO)',
                '_yoast_wpseo_focuskw' => 'Focus Keyphrase (Yoast SEO)',
                'product_cat' => 'Category Name',
            );
        }

        return $labels[$field_name];
    }

    public function mylang_render_post_field()
    {

        return array(
            'title' => 'Title',
            'description' => 'HTML Text',
            'excerpt'  => 'Excerpt',
            'meta_desc' => 'Meta Description (Yoast SEO)',
            'meta_keyword' => 'Focus Keyphrase (Yoast SEO)',
            'category' => 'Category Name',
        );
    }

    public function mylang_render_page_items_field()
    {
        return array(
            'title' => 'Title',
            'description' => 'HTML Text',
            'excerpt'  => 'Excerpt',
            'meta_desc' => 'Meta Description (Yoast SEO)',
            'meta_keyword' => 'Focus Keyphrase (Yoast SEO)',
        );
    }

    public function mylang_render_product_items_field()
    {

        $checkboxes = array(
            'title' => 'Title (WooCommerce)',
            'description' => 'HTML Text (WooCommerce)',
            'excerpt'  => 'HTML Short Description (WooCommerce)',
            'meta_desc' => 'Meta Description (Yoast SEO)',
            'meta_keyword' => 'Focus Keyphrase (Yoast SEO)',
            'product_cat' => 'Category Name',
        );

        if (!defined('WC_VERSION') || !defined('WCML_VERSION')) {
            // no woocommerce :(
            $disabled = "disabled = 'disabled'";
        } else {
            $disabled = '';
        }

        return array('disabled' => $disabled, 'checkboxes' => $checkboxes);
    }

    public function mylang_render_translate_to_field()
    {
        //moved fields out to raw html
    }

    public function mylang_render_update_mode_field()
    {
        //moved fields out to raw html
    }

    public function mylang_duplicate_category($cat_id, $taxonomy, $target_lang)
    {
        $field = 'id';
        if ($taxonomy == 'product_cat') {
            $field = 'term_taxonomy_id';
        }
        $original_cat = get_term_by($field, (int) $cat_id, $taxonomy);
        global $wpml_term_translations;

        $new_term_args = array(
            'term'                => $original_cat->name,
            // 'slug'                => $original_cat->slug,
            'taxonomy'            => $taxonomy,
            'lang_code'           => $target_lang,
            'original_tax_id'     => $cat_id,
            'update_translations' => true,
            'trid'                => $wpml_term_translations->get_element_trid($original_cat->term_taxonomy_id),
            'source_language'     => $wpml_term_translations->get_element_lang_code(
                $original_cat->term_taxonomy_id
            )
        );

        $new_translated_term = WPML_Terms_Translations::create_new_term($new_term_args);

        $new_cat_id = $new_translated_term['term_taxonomy_id'];

        return $new_cat_id;
    }

    public function mylang_duplicate_category_v2($cat_id, $taxonomy, $target_lang)
    {
        global $sitepress, $wpml_term_translations;
        $original_cat = get_term_by('term_taxonomy_id', $cat_id, 'product_cat');
        $original_trid = $wpml_term_translations->get_element_trid($original_cat->term_taxonomy_id);
        $original_meta_data = get_term_meta($original_cat->term_id);

        $new_translated_term = WPML_Post_Edit_Ajax::save_term_ajax($sitepress, $target_lang, $taxonomy, NULL, $original_cat->name, $original_trid, NULL, []);

        $new_cat_id = $new_translated_term->term_taxonomy_id;

        mylang_translation_log("New category id: " . $new_cat_id);

        return $new_cat_id;
    }

    /**
     * @param array $term
     * @param array $meta_data
     * @param bool  $is_new_term
     *
     * @return bool
     */
    // private static function mylang_add_term_metadata($term, $meta_data, $is_new_term)
    // {
    //     global $sitepress;

    //     foreach ($meta_data as $meta_key => $meta_value) {
    //         delete_term_meta($term['term_id'], $meta_key);
    //         $data = maybe_unserialize(stripslashes($meta_value));
    //         if (!add_term_meta($term['term_id'], $meta_key, $data)) {
    //             throw new RuntimeException(sprintf('Unable to add term meta form term: %d', $term['term_id']));
    //         }
    //     }

    //     $sync_meta_action = new WPML_Sync_Term_Meta_Action($sitepress, $term['term_taxonomy_id'], $is_new_term);
    //     $sync_meta_action->run();

    //     return true;
    // }

    /**
     * Duplicates a post & its meta and it returns the new duplicated Post ID
     * @param  [int] $post_id The Post you want to clone
     * @param  [string] $target_lang The cloned post language
     * @return [int] The duplicated Post ID
     */
    public function mylang_duplicate_post($post_id, $target_lang = '', $type)
    {
        global $sitepress;
        $title   = get_the_title($post_id);
        // $oldpost = get_post($post_id);
        $post    = array(
            'post_title' => $title,
            'post_status' => 'publish',
            'post_type' => $type
        );

        $new_post_id = wp_insert_post($post);
        // Copy post metadata
        $data = get_post_custom($post_id);
        foreach ($data as $key => $values) {
            foreach ($values as $value) {
                add_post_meta($new_post_id, $key, maybe_unserialize($value)); // it is important to unserialize data to avoid conflicts.
            }
        }

        // Copy taxonomies
        // $taxonomies = get_object_taxonomies($type);
        // foreach ($taxonomies as $taxonomy) {
        //     $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
        //     wp_set_object_terms($new_post_id, $terms, $taxonomy);
        // }

        $taxonomy = 'category';
        if ($type == 'product') {
            $taxonomy = 'product_cat';
        }

        // Duplicate taxonomies in target_lang
        if ($type == 'product' || $type == 'post') {
            // $taxonomies = get_object_taxonomies($type);
            // foreach ($taxonomies as $taxonomy) {

            $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
            $terms_in_target_lang = array();

            foreach ($terms as $term) {
                $name = get_term($term)->name;

                $is_translated = apply_filters('wpml_element_has_translations', NULL, $term, $taxonomy, $target_lang);
                $translated_object_id = apply_filters('wpml_object_id', $term, $taxonomy, true, $target_lang);

                if (!$is_translated || (int)$translated_object_id == (int)$term) {
                    $term_in_target_lang = $this->mylang_duplicate_category($term, $taxonomy, $target_lang);
                    if ($term_in_target_lang) {
                        $terms_in_target_lang[] = $term_in_target_lang;
                    }
                } else {
                    $terms_in_target_lang[] = $translated_object_id;
                }

                // $switch_lang = new WPML_Temporary_Switch_Language($sitepress, $target_lang);
                // $is_new_term = !term_exists($name, $taxonomy);
                // $switch_lang->restore_lang();

                // if ($is_new_term) {
                // $term_in_target_lang = $this->mylang_duplicate_category($term, $taxonomy, $target_lang);
                // if ($term_in_target_lang) {
                //     $terms_in_target_lang[] = $term_in_target_lang;
                // }
                //}
                // mylang_translation_log("Term: " . $term . " is new: " . $is_new_term);
            }

            wp_set_object_terms($new_post_id, $terms_in_target_lang, $taxonomy);
            // }
        }


        // https://wpml.org/wpml-hook/wpml_element_type/
        $wpml_element_type = apply_filters('wpml_element_type', $type);

        // get the language info of the original post
        // https://wpml.org/wpml-hook/wpml_element_language_details/
        $get_language_args = array('element_id' => $post_id, 'element_type' => $type);
        $original_post_language_info = apply_filters('wpml_element_language_details', null, $get_language_args);

        // mylang_translation_log("Original post language info" . print_r($original_post_language_info, true));

        $set_language_args = array(
            'element_id'    => $new_post_id,
            'element_type'  => $wpml_element_type,
            'trid'   => $original_post_language_info->trid,
            'language_code' => $target_lang,
            'source_language_code' => $original_post_language_info->language_code
        );

        // mylang_translation_log("SET LANGUAGE ARGS: " . print_r($set_language_args, true));

        do_action('wpml_set_element_language_details', $set_language_args);

        return $new_post_id;
    }

    public function mylang_wpml_is_active()
    {
        $wpml_is_active = false;
        if (defined('ICL_SITEPRESS_VERSION') && !ICL_PLUGIN_INACTIVE && class_exists('SitePress')) {
            $wpml_is_active = true;
        }
        return $wpml_is_active;
    }

    public function mylang_wpml_not_active_notice()
    {
        if (!$this->mylang_wpml_is_active()) {
        ?>
            <div id="message" class="notice notice-error is-dismissible">
                <p>myLang for WPML cannot work without WPML (<a href="https://wpml.org/">https://wpml.org/</a>), please install and activate the WPML plugin. </p>
            </div>
            <h3>myLang for WPML cannot work without WPML (<a href="https://wpml.org/">https://wpml.org/</a>), please install and activate the WPML plugin. </h3>
<?php
            exit;
        }
    }

    public function mylang_download_debug_info()
    {
        include_once WPML_PLUGIN_PATH . '/inc/functions-debug-information.php';
        $debug_info = get_debug_info();
        $debug_data = $debug_info->run();

        $filename = 'mylang_debug_log';

        mylang_translation_log($debug_data, 'a', $filename);
        $this->mylang_download_log($filename . '.log');
    }

    private function mylang_api_error_message($status_code)
    {
        switch ((int)$status_code) {
            case 400:
                $message = 'Bad Request -- Your request is invalid.';
                break;
            case 401:
                $message = 'Unauthorized -- Your API key is wrong.';
                break;
            case 403:
                $message = 'Forbidden -- The requested is hidden for administrators only.';
                break;
            case 404:
                $message = 'Not Found -- The requested page was not found.';
                break;
            case 405:
                $message = 'Method Not Allowed -- You tried to access with an invalid method.';
                break;
            case 408:
                $message = 'Request Timeout -- The server took too long to respond. Try again later.';
                break;
            case 456:
                $message = 'Negative balance -- Please top up your account balance.';
                break;
            case 457:
                $message = 'Something is wrong -- Cannot to translate.';
                break;
            case 458:
                $message = 'No language -- Change the engine number.';
                break;
            case 500:
                $message = 'Internal Server Error -- We had a problem with our server. Try again later.';
                break;

            default:
                $message = 'Uknown error';
                break;
        }

        return $message;
    }

    /**
     *  show all lines from line 
     */
    private function mylang_logs_started_by_line($file, $xline = 0)
    {
        $currline = 0;
        $handle = fopen($file, "r");
        $text = '';
        while (!feof($handle)) {
            $line = fgets($handle);
            if ($xline <= $currline) {
                $text .= $line;
            }
            $currline++;
        }
        fclose($handle);

        return array($text, $currline);
    }

    /** Glossary functions */
    private function replaceGlossary(&$text, $languageCode)
    {
        $languageCode = (string)$languageCode;
        $replaces = [];
        $i = 2;
        foreach ($this->glossary as $item) {
            if (!trim($item['original']) || !trim($item['translate'])) {
                continue;
            }
            if (substr($item['translate'], 0, 1) == '{') {
                $translate = json_decode($item['translate'], true);
                if (empty($translate[$languageCode])) {
                    continue;
                }
                $translate = $translate[$languageCode];
            } else {
                $translate = $item['translate'];
            }

            //mylang_translation_log("TEXT before replace: $text /n");

            $original = htmlspecialchars($item['original']);
            $preg = '/(\W|^)' . preg_quote($original, '/') . '(\W|$)/u';
            $text = preg_replace($preg, "$1|$i| {$original} |$i|$2", $text, -1, $count);

            // mylang_translation_log("PREG: $preg TEXT after replace: $text /n");
            // mylang_translation_log("REPLACEMENT: $1|$i| {$item['original']} |$i|$2");

            if ($count == 0) {
                continue;
            }
            //$len = max(5, strlen($item['original']) * 3);
            $replaces["/(\||\s)\s*$i\s*\|.{1,}?(\||\s)\s*$i\s*\|/ui"] = $translate;
            // $replaces["/(\||\s)\s*$i\s*\|.{1,}?(\||\s)\s*$i\s*\|*/ui"] = $translate;
            // $replaces["/\|\s*$i\s*(\||\s).{1,}?\|\s*$i\s*(\||\s)/ui"] = $translate;

            $i++;
            if ($i == 11 || $i == 111) {
                $i++;
            }
        }

        //mylang_translation_log("REPLACES: " . print_r($replaces, true) ." /n");

        return $replaces;
    }

    public function add_item()
    {
        global $wpdb;
        $glossary_table = $wpdb->prefix . "mylang_glossary";

        $response = 'OK';

        $original = sanitize_text_field(trim($_POST['original']));
        $original = esc_sql($original);
        $translate = array_map('trim', $_POST['translate']);
        $errors = [];
        if (!$original) {
            $errors['original'] =  'Error: glossary required';
        }
        foreach ($translate as $k => $v) {
            if (!$v) {
                $errors[$k] = 'Error: glossary required';
            }
        }
        if (empty($errors['original'])) {
            $andWhere = '';
            $origQ = $wpdb->query("SELECT COUNT(id) FROM $glossary_table WHERE `original`='$original'$andWhere");
            $q = $wpdb->get_var($origQ);
            if ($q > 0) {
                $errors['original'] = 'Error: glossary exists';
            }
        }
        if (count($errors) == 0) {
            if (count($translate) > 0 && count(array_unique($translate)) == 1) {
                $translate = array_values($translate)[0];
            } else {
                $translate = json_encode($translate);
            }
            $translate = sanitize_text_field($translate);
            $translate = esc_sql($translate);

            $wpdb->query("INSERT INTO $glossary_table SET `original`='$original', `translate`='$translate'");
            $editQ = "SELECT count(id) FROM $glossary_table";
            $result = $wpdb->get_var($editQ);

            $pageSize = 25;
            $count = intval($result);
            $pageCount = max(ceil($count / $pageSize), 1);
            $response = [
                'success' => true,
                'page' => $pageCount - 1,
            ];
        } else {
            $response = [
                'success' => false,
                'errors' => $errors,
            ];
        }

        echo json_encode($response);
        exit;
    }

    public function get_page()
    {
        global $wpdb;
        $glossary_table = $wpdb->prefix . "mylang_glossary";

        $response = 'OK';

        $search = sanitize_text_field($_GET['search']);
        $scaped_search = esc_sql($search);
        $where = "";
        if ($search) {
            $where = "WHERE original LIKE '%$scaped_search%'";
        }
        $pageSize = 25;
        $query = "SELECT count(id) FROM $glossary_table $where";
        $result = $wpdb->get_var($query);
        $count = intval($result);
        $pageCount = max(ceil($count / $pageSize), 1);
        $page = max(min(intval($_GET['page'] ?? 0), $pageCount - 1), 0);
        $start = $page * $pageSize;
        $queryGl = "SELECT * FROM $glossary_table $where ORDER BY id ASC LIMIT $start, $pageSize";
        $result = $wpdb->get_results($queryGl);
        $response = [
            'items' => $result,
            'pageCount' => $pageCount,
            'page' => $page,
        ];

        echo json_encode($response);
        exit;
    }

    public function edit_item()
    {
        global $wpdb;
        $glossary_table = $wpdb->prefix . "mylang_glossary";
        $response = 'OK';

        $original = sanitize_text_field(trim($_POST['original']));
        $original = esc_sql($original);
        $translate = array_map('trim', $_POST['translate']);
        $errors = [];
        if (!$original) {
            $errors['original'] = 'Error: glossary required';
        }
        foreach ($translate as $k => $v) {
            if (!$v) {
                $errors[$k] = 'Error: glossary required';
            }
        }
        if (empty($errors['original'])) {
            $andWhere = " AND id != " . intval($_POST['id']);
            $origQ = "SELECT COUNT(id) FROM $glossary_table WHERE `original`='$original'$andWhere";
            $q = $wpdb->get_var($origQ);
            if ($q > 0) {
                $errors['original'] = 'Error: glossary exists';
            }
        }
        if (count($errors) == 0) {
            if (count($translate) > 0 && count(array_unique($translate)) == 1) {
                $translate = array_values($translate)[0];
            } else {
                $translate = json_encode($translate, JSON_UNESCAPED_UNICODE);
            }
            $translate = sanitize_text_field($translate);
            $translate = esc_sql($translate);

            $wpdb->query("UPDATE $glossary_table SET `original`='$original', `translate`='$translate' WHERE id = " . intval($_POST['id']));
            $editQ = "SELECT count(id) FROM $glossary_table WHERE id <= " . intval($_POST['id']);
            $result = $wpdb->get_var($editQ);

            $pageSize = 25;
            $count = intval($result);
            $pageCount = max(ceil($count / $pageSize), 1);
            $response = [
                'success' => true,
                'page' => $pageCount - 1,
            ];
        } else {
            $response = [
                'success' => false,
                'errors' => $errors,
            ];
        }

        echo json_encode($response);
        exit;
    }

    public function remove_item()
    {
        global $wpdb;
        $glossary_table = $wpdb->prefix . "mylang_glossary";

        $response = 'OK';

        $id = intval($_POST['id']);
        $wpdb->query("DELETE FROM $glossary_table WHERE id = $id");
        $remQuery = "SELECT count(id) FROM $glossary_table WHERE id <= $id";
        $result = $wpdb->get_results($remQuery);
        $pageSize = 25;
        $count = intval($result);
        $pageCount = max(ceil($count / $pageSize), 1);
        $response = [
            'success' => true,
            'page' => $pageCount - 1,
        ];

        echo json_encode($response);
        exit;
    }
}
