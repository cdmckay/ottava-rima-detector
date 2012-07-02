<?php

class CMUDict {

    private $keys = array();
    private $values = array();
    private $count = 0;

    private function __construct() {
        $handle = fopen(__DIR__ . '/cmu-dict.txt', 'r');
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                // Handle comments.
                if (starts_with(';;;', $buffer)) {
                    continue;
                }
                list($word, $imploded_phonemes) = explode('  ', rtrim($buffer));
                $phonemes = explode(' ', $imploded_phonemes);
                $modified_phonemes = array();
                foreach ($phonemes as $phoneme) {
                    $modified_phoneme = $phoneme;
                    if (strlen($modified_phoneme) > 2) {
                        // Remove stress from phonemes, as poems can play with stress a lot.
                        $modified_phoneme = substr($modified_phoneme, 0, 2);
                    }
                    $modified_phonemes[] = $modified_phoneme;
                }
                // Ignoring alternate pronunciations for now.
                if (!ends_with(')', $word)) {
                    $this->values[$word] = $modified_phonemes;
                }
            }
            if (!feof($handle)) {
                echo "Unexpected fgets() fail.\n";
            }
            fclose($handle);
        }
        $this->keys = array_keys($this->values);
        $this->count = count($this->values);
    }

    // No cloning!
    function __clone() {

    }

    static function get() {
        static $instance = null;
        if ($instance === null) {
            $instance = new CMUDict();
        }
        return $instance;
    }

    function getPhonemes($word) {
        $uppercased_word = strtoupper($word);
        $result = $this->values[$uppercased_word];
        if ($result !== null) {
            return $result;
        }

        // If it ends with 'd, replace with ed.
        if (ends_with("'D", $uppercased_word)) {
            return $this->getPhonemes(substr($uppercased_word, 0, -2) . 'ED');
        }

        // If it has a - or ' in it, try splitting it and returning the words separate.
        $split_words = null;
        if (contains('-', $uppercased_word)) {
            $split_words = explode('-', $uppercased_word);
        } else if (contains("'", $uppercased_word) && !ends_with("'S", $uppercased_word)) {
            $split_words = explode("'", $uppercased_word);
        }
        if (!empty($split_words)) {
            $merged_phonemes = array();
            foreach ($split_words as $split_word) {
                $phonemes = $this->getPhonemes($split_word);
                if ($phonemes === null) {
                    return null;
                }
                $merged_phonemes = array_merge($merged_phonemes, $phonemes);
            }
            return $merged_phonemes;
        }

        return null;
    }

    function getRandomWord() {
        $word = null;
        $suitable = false;
        while (!$suitable) {
            $word = strtolower($this->keys[mt_rand(0, $this->count - 1)]);
            // Ensure it starts with a letter and not punctuation.
            if (preg_match('/^[a-z]/', $word)) {
                $suitable = true;
            }
        }
        return $word;
    }

}
