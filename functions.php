<?php

function contains($value, $str) {
    return strstr($str, $value) !== false;
}

function starts_with($prefix, $str) {
    return !strncmp($str, $prefix, strlen($prefix));
}

function ends_with($suffix, $str) {
    return substr($str, -strlen($suffix)) === $suffix;
}

function last($countable) {
    return $countable[count($countable) - 1];
}

function str_last($str) {
    return $str[strlen($str) - 1];
}
