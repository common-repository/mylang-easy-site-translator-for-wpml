<?php

/**
 * Glossary page
 */
defined('ABSPATH') || exit;
?>
<div style="display:inline;">
    <img width="45" height="45" src="<?php echo esc_url($this->plugin_logo); ?>">
    <h1 style="display:inline-block;vertical-align:top;"> myLang - Site Translator / Magic Button</h1>
</div>
<div>
    <p>
        <font style="vertical-align: inherit;">
            <font style="vertical-align: inherit;">The glossary is used to get your own version of the word translation. Use the glossary to prevent translation for untranslatable words, such as trade names, product names, brands, and company names.</font>
        </font>
    </p>
    <p>
        <font style="vertical-align: inherit;">
            <font style="vertical-align: inherit;">
                You can write down both words and expressions. Write words case sensitive: Apple and apple are different words.
            </font>
        </font>
    </p>
</div>
<input id="glossary-table_search" class="form-control pull-right" style="max-width:300px" type="text" placeholder="Search..." />
<button class="button button-primary button-md" id="glossary-table_add">Add</button>
<div>&nbsp;</div>
<div class="table-responsive">
    <table class="table" id="glossary-table">
        <thead>
            <tr>
                <th>Original</th>
                <?php
                foreach ($active_languages as $language) {
                    $lang_flag = $language['country_flag_url'];
                    $lang_name = $language['native_name'];
                ?>
                    <th><img src="<?php echo esc_url($lang_flag); ?>" title="<?php echo esc_html($lang_name); ?>"> <?php echo esc_html($lang_name); ?></th>
                <?php } ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<ul class="pagination" id="glossary-paginator">
</ul>