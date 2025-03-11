<?php

$virtual_path = 'Virtual@generics/cache';

$cached = opcache_is_script_cached($virtual_path);

spl_autoload_register(fn () => include $virtual_path, true,true);

if ($cached) {
    var_export(new Virtual instanceof Virtual);
}
