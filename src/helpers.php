<?php

if (! function_exists('backpack_url')) {
    /**
     * Appends the configured backpack prefix and returns
     * the URL using the standard Laravel helpers.
     *
     * @param $path
     * @return string
     */
    function backpack_url($path = null, $parameters = [], $secure = null)
    {
        $path = ! $path || (substr($path, 0, 1) == '/') ? $path : '/'.$path;

        return url(config('backpack.base.route_prefix', 'admin').$path, $parameters, $secure);
    }
}

if (! function_exists('backpack_authentication_column')) {
    /**
     * Return the username column name.
     * The Laravel default (and Backpack default) is 'email'.
     *
     * @return string
     */
    function backpack_authentication_column()
    {
        return config('backpack.base.authentication_column', 'email');
    }
}

if (! function_exists('backpack_form_input')) {
    /**
     * Parse the submitted input in request('form') to an usable array.
     * Joins the multiple[] fields in a single key and transform the dot notation fields into arrayed ones.
     *
     *
     * @return array
     */
    function backpack_form_input()
    {
        $input = request('form') ?? [];

        $result = [];
        foreach ($input as $row) {
            // parse the input name to extract the "arg" when using HasOne/MorphOne (address[street]) returns street as arg, address as key
            $start = strpos($row['name'], '[');
            $input_arg = null;
            if ($start !== false) {
                $end = strpos($row['name'], ']', $start + 1);
                $length = $end - $start;

                $input_arg = substr($row['name'], $start + 1, $length - 1);
                $input_arg = strlen($input_arg) >= 1 ? $input_arg : null;
                $input_key = substr($row['name'], 0, $start);
            } else {
                $input_key = $row['name'];
            }

            if (is_null($input_arg)) {
                if (! isset($result[$input_key])) {
                    $result[$input_key] = $start ? [$row['value']] : $row['value'];
                } else {
                    array_push($result[$input_key], $row['value']);
                }
            } else {
                $result[$input_key][$input_arg] = $row['value'];
            }
        }

        return $result;
    }
}

if (! function_exists('backpack_users_have_email')) {
    /**
     * Check if the email column is present on the user table.
     *
     * @return string
     */
    function backpack_users_have_email()
    {
        $user_model_fqn = config('backpack.base.user_model_fqn');
        $user = new $user_model_fqn();

        return \Schema::hasColumn($user->getTable(), backpack_authentication_column());
    }
}

if (! function_exists('backpack_avatar_url')) {
    /**
     * Returns the avatar URL of a user.
     *
     * @param $user
     * @return string
     */
    function backpack_avatar_url($user)
    {
        switch (config('backpack.base.avatar_type')) {
            case 'gravatar':
                if (backpack_users_have_email()) {
                    return Gravatar::fallback(config('backpack.base.gravatar_fallback'))->get($user->email);
                }
                break;
            default:
                return method_exists($user, config('backpack.base.avatar_type')) ? $user->{config('backpack.base.avatar_type')}() : $user->{config('backpack.base.avatar_type')};
                break;
        }
    }
}

if (! function_exists('backpack_middleware')) {
    /**
     * Return the key of the middleware used across Backpack.
     * That middleware checks if the visitor is an admin.
     *
     * @param $path
     * @return string
     */
    function backpack_middleware()
    {
        return config('backpack.base.middleware_key', 'admin');
    }
}

if (! function_exists('backpack_guard_name')) {
    /*
     * Returns the name of the guard defined
     * by the application config
     */
    function backpack_guard_name()
    {
        return config('backpack.base.guard', config('auth.defaults.guard'));
    }
}

if (! function_exists('backpack_auth')) {
    /*
     * Returns the user instance if it exists
     * of the currently authenticated admin
     * based off the defined guard.
     */
    function backpack_auth()
    {
        return \Auth::guard(backpack_guard_name());
    }
}

if (! function_exists('backpack_user')) {
    /*
     * Returns back a user instance without
     * the admin guard, however allows you
     * to pass in a custom guard if you like.
     */
    function backpack_user()
    {
        return backpack_auth()->user();
    }
}

if (! function_exists('mb_ucfirst')) {
    /**
     * Capitalize the first letter of a string,
     * even if that string is multi-byte (non-latin alphabet).
     *
     * @param  string  $string  String to have its first letter capitalized.
     * @param  encoding  $encoding  Character encoding
     * @return string String with first letter capitalized.
     */
    function mb_ucfirst($string, $encoding = false)
    {
        $encoding = $encoding ? $encoding : mb_internal_encoding();

        $strlen = mb_strlen($string, $encoding);
        $firstChar = mb_substr($string, 0, 1, $encoding);
        $then = mb_substr($string, 1, $strlen - 1, $encoding);

        return mb_strtoupper($firstChar, $encoding).$then;
    }
}

if (! function_exists('backpack_view')) {
    /**
     * Returns a new displayable view based on the configured backpack view namespace.
     * If that view doesn't exist, it will load the one from the original theme.
     *
     * @param string (see config/backpack/base.php)
     * @return string
     */
    function backpack_view($view)
    {
        $originalTheme = 'backpack::';
        $theme = config('backpack.base.view_namespace');

        if (is_null($theme)) {
            $theme = $originalTheme;
        }

        $returnView = $theme.$view;

        if (! view()->exists($returnView)) {
            $returnView = $originalTheme.$view;
        }

        return $returnView;
    }
}

if (! function_exists('square_brackets_to_dots')) {
    /**
     * Turns a string from bracket-type array to dot-notation array.
     * Ex: array[0][property] turns into array.0.property.
     *
     * @param $path
     * @return string
     */
    function square_brackets_to_dots($string)
    {
        $string = str_replace(['[', ']'], ['.', ''], $string);

        return $string;
    }
}

if (! function_exists('old_empty_or_null')) {
    /**
     * This method is an alternative to Laravel's old() helper, which mistakenly
     * returns NULL it two cases:
     * - if there is an old value, and it was empty or null
     * - if there is no old value
     * (this is because of the ConvertsEmptyStringsToNull middleware).
     *
     * In contrast, this method will return:
     * - the old value, if there actually is an old value for that key;
     * - the second parameter, if there is no old value for that key, but it was empty string or null;
     * - null, if there is no old value at all for that key;
     *
     * @param  string  $key
     * @param  array|string  $empty_value
     * @return mixed
     */
    function old_empty_or_null($key, $empty_value = '')
    {
        $key = square_brackets_to_dots($key);
        $old_inputs = session()->getOldInput();

        // if the input name is present in the old inputs we need to return earlier and not in a coalescing chain
        // otherwise `null` aka empty will not pass the condition and the field value would be returned.
        if (\Arr::has($old_inputs, $key)) {
            return \Arr::get($old_inputs, $key) ?? $empty_value;
        }

        return null;
    }
}

if (! function_exists('is_multidimensional_array')) {
    /**
     * If any of the items inside a given array is an array, the array is considered multidimensional.
     *
     * @param  array  $array
     * @return bool
     */
    function is_multidimensional_array(array $array)
    {
        foreach ($array as $item) {
            if (is_array($item)) {
                return true;
            }
        }

        return false;
    }
}

if (! function_exists('backpack_pro')) {
    /**
     * Check if the backpack/pro package is installed.
     *
     * @return bool
     */
    function backpack_pro()
    {
        if (! \Composer\InstalledVersions::isInstalled('backpack/pro')) {
            return false;
        }

        return \PackageVersions\Versions::getVersion('backpack/pro');
    }
}
