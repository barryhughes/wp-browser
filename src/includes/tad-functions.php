<?php

    function tad_functions()
    {
        // just a marker
    }

    /**
     * @param $callback A callable function.
     * @param array $whitelist An array of filters that should remain hooked.
     *
     * @return mixed The callback return value.
     */
    function _without_filters($callback, $whitelist = array())
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException('Callback must be callable');
        }

        global $wp_filter, $merged_filters;

        // Save filters and actions state
        $wp_filter_backup = $wp_filter;
        $merged_filters_backup = $merged_filters;

        $whitelist = array_combine($whitelist, $whitelist);
        $wp_filter = array_intersect_key($wp_filter, $whitelist);
        $merged_filters = array_intersect_key($merged_filters, $whitelist);

        $exit = call_user_func($callback);

        // Restore previous state
        $wp_filter = $wp_filter_backup;
        $merged_filters = $merged_filters_backup;

        return $exit;
    }
