<?php
/*
 * Settings for mylang.
 *
 */

defined('ABSPATH') || exit;
// $menu_label = plugins_url('mylang/mylang_logo.png');
$active_languages = $this->mylang_get_active_languages();
$options = get_option($this->option_name);
?>
<div style="display:inline;">
    <img width="45" height="45" src="<?php echo esc_url($this->plugin_logo); ?>">
    <h1 style="display:inline-block;vertical-align:top;"> myLang - Site Translator / Magic Button</h1>
</div>
<div>
    <p>An add-on for WPML that allows you to easily translate all pages, posts and products into 90
        languages with a single click!
    </p>
</div>

<form action="options.php" method="post">
    <?php
    settings_errors();
    settings_fields('mylang_settings');
    // do_settings_sections('mylang');
    ?>

    <table class="form-table">
        <tr>
            <th scope="row">API Token</th>
            <td><?php $this->mylang_render_api_token_field() ?></td>
        </tr>

        <tr>
            <!-- <th scope="row">Language Source For the Mass Translate</th> -->
            <th style="padding-top: 15px;" scope="row">Language Source</th>
            <td style="display: inline-flex;">
                <div style="display: grid;">
                    <label>Mass Translate</label>
                    <select id='srclang-ref' name='mylang_settings[mylang_source_language]'>
                        <option> --Select-- </option>
                        <?php
                        foreach ($active_languages as $language) {
                            $lang_code =  $language['language_code'];
                            if ($lang_code  === $options['mylang_source_language']) {
                        ?>
                                <option id="srclang-<?php echo esc_attr($lang_code) ?>" value="<?php echo esc_attr($lang_code) ?>" selected='selected'> <?php echo esc_html($language['native_name']) ?></option>
                            <?php } else { ?>
                                <option id="srclang-<?php echo esc_attr($lang_code) ?>" value="<?php echo esc_attr($lang_code) ?>"> <?php echo esc_html($language['native_name']) ?></option>
                        <?php }
                        } ?>
                    </select>
                </div>

                <div style="display: grid; margin-left: 30px;">
                    <label>Magic Button</label>
                    <select id='srclang-ref_mb' name='mylang_settings[mylang_source_language_magic_button]'>
                        <option> --Select-- </option>
                        <?php
                        foreach ($active_languages as $language) {
                            $lang_code =  $language['language_code'];
                            if ($lang_code  === $options['mylang_source_language_magic_button']) {
                        ?>
                                <option id="srclangmb-<?php echo esc_attr($lang_code) ?>" value="<?php echo esc_attr($lang_code) ?>" selected='selected'> <?php echo esc_html($language['native_name']) ?></option>
                            <?php } else { ?>
                                <option id="srclangmb-<?php echo esc_attr($lang_code) ?>" value="<?php echo esc_attr($lang_code) ?>"> <?php echo esc_html($language['native_name']) ?></option>
                        <?php }
                        } ?>
                    </select>
                </div>
            </td>
        </tr>

        <!-- <tr>
            <th scope="row">Language Source For Magic Button</th>
            <td>
                <select id='srclang-ref_mb' name='mylang_settings[mylang_source_language_magic_button]'>
                    <option> --Select-- </option>
                    <?php
                    foreach ($active_languages as $language) {
                        $lang_code =  $language['language_code'];
                        if ($lang_code  === $options['mylang_source_language_magic_button']) {
                    ?>

                            <option id="srclangmb-<?php echo esc_attr($lang_code) ?>" value="<?php echo esc_attr($lang_code) ?>" selected='selected'> <?php echo esc_html($language['native_name']) ?></option>
                        <?php } else { ?>
                            <option id="srclangmb-<?php echo esc_attr($lang_code) ?>" value="<?php echo esc_attr($lang_code) ?>"> <?php echo esc_html($language['native_name']) ?></option>
                    <?php }
                    } ?>

                </select>
            </td>
        </tr> -->

        <tr>
            <th scope="row">Translate To</th>
            <td>
                <?php

                $_checkbox = '';

                foreach ($active_languages as $language) {
                    $lang_code = trim($language['language_code']);
                    $lang_flag = $language['country_flag_url'];
                    if ($lang_code === $options['mylang_source_language'] || $lang_code === $options['mylang_source_language_magic_button']) {
                        $disabled = "disabled = disabled";
                    } else {
                        $disabled = '';
                    }

                    $field_checked = isset($options['mylang_translate_to'][$lang_code]) ? (array) $options['mylang_translate_to'][$lang_code] : [];
                ?>
                    <span>
                        <label style="padding-right:5px;">
                            <input class="target_language" <?php echo esc_attr($disabled) ?> id="targetlang-<?php echo esc_attr($lang_code) ?>" type="checkbox" name="mylang_settings[mylang_translate_to][<?php echo esc_attr($lang_code) ?>]" value="1" <?php echo checked(in_array(1, $field_checked), 1, false) ?> />
                            <img class="icl_als_iclflag" src="<?php echo esc_url($lang_flag) ?> " width="18" height="12"> <?php echo esc_html($language['native_name']) ?>
                        </label>
                    </span>

                <?php } ?>

            </td>
        </tr>

        <tr>
            <th scope="row">Update Mode</th>
            <td>
                <select id='mylang_update_mode' name='mylang_settings[mylang_update_mode]'>
                    <?php

                    if (get_option('mylang_update_mode') === false) {
                        update_option('mylang_update_mode', 'empty');
                    }

                    if (isset($options['mylang_update_mode']) && ($options['mylang_update_mode'] === "all")) {
                    ?>
                        <option value='all' selected='selected'>All - Re-translate all items</option>
                        <option value='empty'>Empty - Translate only those without translations</option>
                    <?php } else { ?>
                        <option value='all'>All- Retranslate all</option>
                        <option value='empty' selected='selected'>Empty - Translate only empty</option>
                    <?php } ?>
                </select>

            </td>
        </tr>
        <tr>
            <th scope="row">Update</th>
            <td style="padding-top:0px !important;">
                <table class="form-table">
                    <tr>
                        <td style="padding-top:0px !important;">
                            <h4>Pages</h4>
                            <?php

                            $_page_fields =  $this->mylang_render_page_items_field();
                            $page_options = isset($options['mylang_page_translation_item']) ? $options['mylang_page_translation_item'] : array();

                            foreach ($_page_fields as $setting => $label) {
                                $_checked = isset($page_options[$setting]) ? $page_options[$setting] : 0;
                            ?>
                                <p>
                                    <input class="translate_item" type="checkbox" name="mylang_settings[mylang_page_translation_item][<?php echo esc_html($setting) ?>]" value="1" <?php echo checked(1, $_checked, false) ?> /><?php echo esc_html($label) ?>
                                </p>
                            <?php } ?>
                            <p style="height: 22px;"></p>

                        </td>
                        <td style="padding-top:0px !important;">
                            <h4>Posts</h4>
                            <?php
                            $checkboxes = $this->mylang_render_post_field();

                            $item_options = isset($options['mylang_post_translation_item']) ? $options['mylang_post_translation_item'] : array();

                            foreach ($checkboxes as $setting => $label) {
                                $title_checked = isset($item_options[$setting]) ? $item_options[$setting] : 0;
                            ?>
                                <p>
                                    <input class="translate_item" type="checkbox" name="mylang_settings[mylang_post_translation_item][<?php echo esc_attr($setting) ?>]" value="1" <?php echo checked(1, $title_checked, false) ?> /> <?php echo esc_html($label) ?>
                                </p>
                            <?php } ?>
                        </td>
                        <td style="padding-top:0px !important;">
                            <h4>Products</h4>
                            <?php
                            $_product_fields = $this->mylang_render_product_items_field();

                            $checkboxes = $_product_fields['checkboxes'];
                            $disabled = $_product_fields['disabled'];

                            $item_options = isset($options['mylang_product_translation_item']) ? $options['mylang_product_translation_item'] : array();

                            foreach ($checkboxes as $setting => $label) {
                                $_checked = isset($item_options[$setting]) ? (int)$item_options[$setting] : 0;
                            ?>
                                <p>
                                    <input class="translate_item" type="checkbox" <?php echo esc_attr($disabled) ?> name="mylang_settings[mylang_product_translation_item][<?php echo esc_attr($setting) ?>]" value="1" <?php echo checked(1, $_checked, false) ?> /> <?php echo esc_html($label) ?>
                                </p>
                            <?php } ?>
                        </td>
                    </tr>

                </table>
            </td>

        </tr>
    </table>
    <input type="submit" style="margin-bottom:10px !important;" name="submit" class="button button-primary button-md" value="<?php esc_attr_e('Save'); ?>" />
</form>