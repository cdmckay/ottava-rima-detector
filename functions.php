<?php

/**
 * Returns true if the $value is a substring of $str, false otherwise.
 *
 * @param $value
 * @param $str
 * @return bool
 */
function contains($value, $str) {
    return strstr($str, $value) !== false;
}

/**
 * Returns true if $str starts with $prefix, false otherwise.
 *
 * @param $prefix
 * @param $str
 * @return bool
 */
function starts_with($prefix, $str) {
    return !strncmp($str, $prefix, strlen($prefix));
}

/**
 * Returns true if $str ends with $suffix, false otherwise.
 *
 * @param $suffix
 * @param $str
 * @return bool
 */
function ends_with($suffix, $str) {
    return substr($str, -strlen($suffix)) === $suffix;
}

/**
 * Returns the last element of a countable (like an array).
 *
 * @param $countable
 * @return mixed
 */
function last($countable) {
    return $countable[count($countable) - 1];
}
