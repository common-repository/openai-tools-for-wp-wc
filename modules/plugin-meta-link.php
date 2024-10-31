<?php

function openai_tools_plugin_meta_links($links, $file)
{

    $plugin_file = 'openai-tools-for-wp-wc/openai-tools.php';
    if ($file == $plugin_file) {
        return array_merge(
            $links,
            array(
                '<a target="_blank" href="https://woocat.app" style="color: purple"><div class="wp-menu-image svg" style="background-image: url(&quot;data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACQAAAAkCAMAAADW3miqAAAA0lBMVEUAAAB+U7J+U7J+U7R/U7WAVLN/VLN/U7OAU7N/U7N+UrR9U7R+VLN9UrKAVbN9UbJ/UrR/UrN/VbGAVbV/VLP///99V6R9V6eCWLV/WaX9/f7Rw+Dy7vf28/r08Pjw6vbs5vPc0efazua/q9KNa7CJZq37+v359/vo4fG5pM63oMyhgciulseSbb+JYbqBXKfm3e7i2e3d0evIttu8qNCrjs6lisCihr6Qar6OZ7yXd7aRcLJ+Vave1OrMu+HAq9q+qNmrj86xmsmqkcSafLiDXqiBiYQKAAAAFHRSTlMA/fzJJ/bz49zTqXFqYFQ/N5ONMNbkO40AAAFtSURBVDjLhdOHboMwFIVhp2n2bOtzk0B2yN57drfv/0q9RiAgZfxCMkYfAmEszB6zqQefUtlHYfcSk4HFLPYkQ3tWpiwjKjOSkQlRikYlEY9GcZGUYbXQkjIpYqEIAH+GcFR1UIUCcpDbXO+Ug6gzsK4ZaAQigLgJURt9qgMjnjWBlQd1YRDpuLBmx82oBe7oRhfsiEGnhqFyNYAP4pkbLdGmDjD4gX7AWPkZZjxobkTqVm6How6DatC66BOhcYfW6I0B9CfokY5JD1/UwbcHdQEoiiUBdYCWQJMHD7pCvYoO1KgHqEdtAfx6kPmOnMbIm2dZDDI70F1zZ4FvFSfi7PObbMBGXLUOs0F9vd2fpZ26+mmjBQLTpI0AbKbn015vav+Ng6ae/3H6thmNVt33OU9MlDDRh+QWc+lbQmTMPWH16osyoqiGpoUavqhobfNTG9xwEbDNRV5GlBdcPGqTm+XCTE7YFdL+Il0Qqj+LG2iB2eMRPwAAAABJRU5ErkJggg==&quot;) !important;display: inline-flex;width: 21px;height: 20px;background: no-repeat;background-size: cover;align-items: flex-end;" aria-hidden="true"></div> [iOS] WooCat - Order Alert | Sales Report</a>'
            )
        );
    }

    return $links;
}
add_filter('plugin_row_meta', 'openai_tools_plugin_meta_links', 10, 2);
