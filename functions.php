<?php

function contains($value, $str) {
    return strstr($str, $value) !== false;
}

function starts_with($prefix, $str) {
    return substr($str, 0, strlen($prefix)) === $prefix;
}

function ends_with($suffix, $str) {
    return substr($str, -strlen($suffix)) === $suffix;
}

function last($countable) {
    if (is_string($countable)) {
        return $countable[strlen($countable) - 1];
    }
    return $countable[count($countable) - 1];
}
