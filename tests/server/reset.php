<?php
if (extension_loaded('Zend OPcache') && ini_get('opcache.enable')) {
    opcache_reset();
    echo 'ok';
}
