<?php
defined('ABSPATH') || exit;
$queue_count = $this->mylang_queue_count();
$completed_count = $this->mylang_complete_queue_count();

$options = get_option($this->option_name);

if (!empty($options) && isset($options['mylang_api_token'])) {

    if ($completed_count < 1  || $queue_count < 1) {
        $_progress = '0';
    } else {
        $_progress = floor(($completed_count / $queue_count) * 100);
    }

    $is_cancelled = get_option('mylang_queue_cancelled', '9');

    if (($_progress > 0) && ($_progress < 100) && ($is_cancelled != '10')) {
        $translate = true;
    } else {
        $translate = false;
    }

    $progress = $_progress . '%';
    //$_text = ($is_cancelled != '10' ) ? $progress : $progress." Translation cancelled!";
    //10 = Cancelled
    //9= Not cancelled
    $cancelled = false;
    if ((int)$is_cancelled == 10 && $_progress > 0 && $_progress < 100) {

        $text = "Translation cancelled at " . $progress;
        $_class = "notransition";
        $cancelled = true;
    } else {
        $text = $progress;
        $_class = "";
    }

?>
    <style>
        .progress-bar {
            float: left;
            height: 100%;
            width: 0%;
        }

        .bar-con {
            background-color: #ccc;
            height: 20px;
            margin-bottom: 16px;
        }

        .bar-two .progress-bar {
            background-color: #007cba;
            transition: width ease-in-out 2s;
            -webkit-transition: width ease-in-out 2s;
            -moz-transition: width ease-in-out 2s;
            -o-transition: width ease-in-out 2s;
            text-align: center;
            position: relative;
            color: #fff;
        }

        /* .notice {
            width: fit-content !important;
            padding-left: 2px !important;
            padding-right: 2px !important;
        } */
        .notransition {
            -webkit-transition: none !important;
            -moz-transition: none !important;
            -o-transition: none !important;
            -ms-transition: none !important;
            transition: none !important;
        }

        .custom-text {
            background-color: #fff;
            width: fit-content;
            padding: 3px;
        }
        #input_calculation_translate{
            padding-left: 15px;

        }
        #div_calculate{
            display: table;
        }
        #input_calculation_translate, #calculation_translate {    
            display: table-cell;
        }
    </style>
    <br><br>
    <h3>Character count</h3>
    <p>Сalculate the number of characters to translate before you start. You can find the prices on the website <a target="_blank" href='https://mylang.me/#pricing'>https://mylang.me/#pricing</a>. The number of purchased and spent characters you can see in your dashboard <a target="_blank" href='https://board.mylang.me/login/'>https://board.mylang.me/login/</a></p>

    <div id="div_calculate">
        <button type="button"  id="calculation_translate" class="button button-danger"><?php esc_attr_e('Calculate'); ?></button>
        <span id="input_calculation_translate"></span>
    </div>
    <div id="div_notice"></div>
    <br>
    <br>
    <h4 style="text-align:center;">
        <button type="button" data-continue-translation="<?php echo esc_attr( $translate ); ?>" id="translate" class="button button-primary"><?php esc_attr_e('Translate my site'); ?></button>
    </h4>
    <p class='lead'>Translation Progress</p>
    <div class="bar-two bar-con" id="progress-container">
        <div class="progress-bar" id="progressbar" data-cancelled="<?= esc_attr( $cancelled ); ?>" data-percent="<?php echo esc_attr( $progress ) ?>"><?php echo esc_html( $text ) ?></div>
    </div>

    <div style="padding:10px 10px 10px 0px;" id="progressbar-container">
        <p style="margin: 0.3em 0 !important; padding-bottom: 10px; padding-left: 2px;">Please contact support at <strong><a href="mailto:f1@mylang.me">f1@mylang.me</a></strong> for a help if you do not see the progress bar
            moving. We are requesting debugging information, <a id="mylang-debug" href="<?php echo admin_url('admin.php?page=mylang&download_debug=yes'); ?>">attach this file</a> to your email.</p>
        <a id="mylang-cancel" href="<?php echo esc_url( admin_url('admin.php?page=mylang&cancel=yes') ); //phpcs:ignore 
                                    ?>" class="button button-primary">Cancel
        </a>
    </div>

    <div>
        <br>
        <p>The translation will continue even if you leave this page. Click Cancel if you want to stop the
            translation process.</p>
    </div>
    <textarea id="error-log" wrap="off" style="font-size:10px;width:100%;height:150px;background-color: #fff !important;" rows="16" readonly="readonly"><?php echo esc_html( $log ); ?></textarea>
    <div style="padding:10px;">
        <a href="<?php echo esc_url( admin_url('admin.php?page=mylang&download=yes') ); //phpcs:ignore 
                    ?>" class="button button-primary">Download Detailed Last Log
        </a>

        <a href="<?php echo esc_url( admin_url('admin.php?page=mylang&clear=yes') ); //phpcs:ignore 
                    ?>" class="button button-danger">Clear Translation Logs
        </a>
    </div>
    <?php } else { ?>
        <div class="error settings-error notice is-dismissible">
            <p>
                <?php
                echo esc_html__('API token setting is missing, please configure API token and try again', 'mylang');
                ?>
            </p>
            <p>
                <a href="<?php echo esc_url( admin_url('admin.php?page=my-lang-settings') ); //phpcs:ignore 
                            ?>" class="button">Go to myLang settings
                </a>
            </p>
        </div>
    <?php } ?>