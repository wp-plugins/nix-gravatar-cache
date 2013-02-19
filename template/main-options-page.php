<?php $cache_info = $this->get_cache_info(); ?>
<div class="wrap">
    <div id="icon-options-general" class="icon32"><br></div>
    <h2><?php _e( $this->plugin_name ); ?></h2>

    <form method="post" class="gravatar_cache_form">
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e( 'Activate Cache Gravatar:' );?></th>
                <td>
                    <label>
                        <input type="checkbox" name="nf_c_a_options[active]" value="1" <?php checked( $options[0]['active'], 1 ); ?> />
                       <?php _e( 'On / Off' ); ?>
                    </label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e( 'TTL of cache:' );?></th>
                <td>
                    <label> <?php _e( 'Day' ); ?>: <input type="text" name="nf_c_a_options[ttl_day]" value="<?php echo $options[0]['ttl_day']; ?>" size="2"/></label>
                    <label> <?php _e( 'Hour' ); ?>: <input type="text" name="nf_c_a_options[ttl_hour]" value="<?php echo $options[0]['ttl_hour']; ?>" size="2"/></label>
                    <label> <?php _e( 'Minute' ); ?>: <input type="text" name="nf_c_a_options[ttl_min]" value="<?php echo $options[0]['ttl_min']; ?>" size="2"/></label>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" name="nf_c_a_submit" id="submit" class="button button-primary" ><?php _e('Save Changes'); ?></button>
            <button class="button" name="nf_clear_cache" <?php echo $cache_info['amount'] == 0 ? 'disabled="disabled"': ''; ?>><?php _e('Clear Cache'); ?>
                <span class="cache count-<?php echo $cache_info['amount']; ?> ">
                    <span class="clear-count"><?php echo '('.$cache_info['amount'] .' files / '.$cache_info['used_space'].')' ?></span>
                </span>
            </button>
        </p>
    </form>
</div><!-- .wrap -->