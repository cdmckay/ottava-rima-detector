<?php

require_once __DIR__ . '/functions.php';

/**
 * Determines the ottava rima fitness of the stanza.
 *
 * @param $stanza
 * @param string $delimiter
 * @param int $syllable_tolerance
 * @return bool|int|number
 * @throws InvalidArgumentException
 */
function ottava_rima_fitness($stanza, $delimiter = "\n", $syllable_tolerance = 2) {
    if (!is_string($stanza)) {
        throw new InvalidArgumentException('The stanza must be a string.');
    }
    if (!is_string($delimiter)) {
        throw new InvalidArgumentException('The delimiter must be a string.');
    }

    // Score starts at 0.
    $score = 0;

    // Separate the stanza into lines.
    $lines = explode($delimiter, trim($stanza));

    // Ensure there are the correct amount of lines.
    if (count($lines) !== 8) {
        $score += abs(8 - count($lines)) * 20;

        // Ensures there are at most 8 lines.
        array_splice($lines, 8);

        // If there are less than 8, add on blank lines.
        for ($i = count($lines); $i < 8; $i++) {
            $lines[] = '';
        }
    }

    $min_syllable_count = 10 - $syllable_tolerance;
    $max_syllable_count = 10 + $syllable_tolerance;
    $last_words = array();
    foreach ($lines as $line) {
        // Adapted from:
        // http://stackoverflow.com/questions/790596/split-a-text-into-single-words
        // This will split on a group of one or more whitespace characters, but also suck in any surrounding
        // punctuation characters. It also matches punctuation characters at the beginning or end of the string.
        // This discriminates cases such as "don't" and "he said 'ouch!'"
        $line_words = preg_split('/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', $line, -1, PREG_SPLIT_NO_EMPTY);

        // Ottava rima poems should be iambic pentameter, so we need to make sure there are 10 syllables.
        // We're not going to make sure it's da-DUM da-DUM da-DUM da-DUM da-DUM, even though it should be.
        $syllable_count = 0;
        foreach ($line_words as $line_word) {
            $syllable_count += estimate_syllables($line_word);
        }
        if ($syllable_count < $min_syllable_count && $syllable_count > $max_syllable_count) {
            $score += min(10, abs(10 - $syllable_count));
            if (PHP_SAPI === 'cli') {
                echo "'" . implode(' ', $line_words) . "' was estimated to have $syllable_count syllable(s).\n";
            }
        }

        // Get the last words for rhyme detection.
        $last_words[] = count($line_words) > 0 ? last($line_words) : '';
    }

    // Determine whether the a, b, and c rhymes match.
    list($a1, $b1, $a2, $b2, $a3, $b3, $c1, $c2) = $last_words;

    $score += !does_rhyme($a1, $a2) * 10;
    $score += !does_rhyme($a1, $a3) * 10;
    $score += !does_rhyme($a2, $a3) * 10;
    $score += !does_rhyme($b1, $b2) * 10;
    $score += !does_rhyme($b1, $b3) * 10;
    $score += !does_rhyme($b2, $b3) * 10;
    $score += !does_rhyme($c1, $c2) * 10;

    return 160 - $score;
}

/**
 * Estimates the number of syllables in a word.
 *
 * @param $word
 * @return int The non-negative number of syllables.
 */
function estimate_syllables($word) {
    static $arpabet_vowels = array(
        'AO', 'AA', 'IY', 'UW', 'EH', 'IH', 'UH', 'AH', 'AX', 'AE', // Monophthongs
        'EY', 'AY', 'OW', 'AW', 'OY', // Diphthongs
        'ER' // R-colored vowels
    );
    static $english_vowels = array('A', 'E', 'I', 'O', 'U');

    $vowel_count = 0;

    // Count the number of Arpabet vowels in the line.  Failing that, count the English vowels.
    $phonemes = cmu_dict_get($word);
    if ($phonemes === null) {
        // If it ends with S or 'S, then we can still potentially use the CMU dict, since S and 'S aren't vowels.
        $uppercased_word = strtoupper($word);
        if (ends_with("'S", $uppercased_word)) {
            $phonemes = cmu_dict_get(substr($word, 0, -2));
        } else if (ends_with("'S", $uppercased_word)) {
            $phonemes = cmu_dict_get(substr($word, 0, -1));
        }
    }
    if ($phonemes !== null) {
        foreach ($phonemes as $phoneme) {
            if (in_array($phoneme, $arpabet_vowels)) {
                $vowel_count++;
            }
        }
    } else {
        //echo "Using English vowel counting for $word.\n";
        $letters = str_split(strtoupper($word));
        foreach ($letters as $letter) {
            if (in_array($letter, $english_vowels)) {
                $vowel_count++;
            }
        }
    }

    return $vowel_count;
}

/**
 * Tests whether two strings rhyme.
 *
 * @param $str1
 * @param $str2
 * @return bool
 */
function does_rhyme($str1, $str2) {
    $words_found = true;

    $phonemes1 = cmu_dict_get($str1);
    if ($phonemes1 === null) {
        //echo "Word not found: $str1.\n";
        $words_found = false;
    }

    $phonemes2 = cmu_dict_get($str2);
    if ($phonemes2 === null) {
        //echo "Word not found: $str2.\n";
        $words_found = false;
    }

    if (!$words_found) {
        return does_rhyme_metaphone($str1, $str2);
    }

    $last_phoneme1 = last($phonemes1);
    if ($last_phoneme1 === 'ER') {
        $last_phoneme1 = 'R';
    }

    $last_phoneme2 = last($phonemes2);
    if ($last_phoneme2 === 'ER') {
        $last_phoneme2 = 'R';
    }

    $rhymes = $last_phoneme1 === $last_phoneme2;

    if (!$rhymes && PHP_SAPI === 'cli') {
        echo "$str1 and $str2 don't rhyme (method: CMU dict).\n";
    }

    return $rhymes;
}

/**
 * Tests whether two strings rhyme using the Metaphone phonetic algorithm.
 *
 * @param $str1
 * @param $str2
 * @return bool
 */
function does_rhyme_metaphone($str1, $str2) {
    $metaphone1 = metaphone($str1);
    $metaphone2 = metaphone($str2);
    $rhyme = substr($metaphone1, -1) === substr($metaphone2, -1);
    if (!$rhyme && PHP_SAPI === 'cli') {
        echo "$str1 and $str2 don't rhyme (method: metaphone).\n";
    }
    return $rhyme;
}

/**
 * Attempts to retrieve the Arpanet phonemes for an English word.
 *
 * @param $word
 * @return array|null
 */
function cmu_dict_get($word) {
    static $cmu_dict = null;
    if ($cmu_dict === null) {
        $cmu_dict = cmu_dict_read(__DIR__ . '/cmu-dict.txt');
    }

    $uppercased_word = strtoupper($word);
    $result = $cmu_dict[$uppercased_word];
    if ($result !== null) {
        return $result;
    }

    // If it ends with 'd, replace with ed.
    if (ends_with("'D", $uppercased_word)) {
        return cmu_dict_get(substr($uppercased_word, 0, -2) . 'ED');
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
            $phonemes = cmu_dict_get($split_word);
            if ($phonemes === null) {
                return null;
            }
            $merged_phonemes = array_merge($merged_phonemes, $phonemes);
        }
        return $merged_phonemes;
    }

    return null;
}

/**
 * Returns the specified CMUdict-formatted file as a PHP array.
 *
 * @param $path
 * @return array
 */
function cmu_dict_read($path) {
    $cmu_dict = array();

    $handle = fopen($path, 'r');
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
                $cmu_dict[$word] = $modified_phonemes;
            }
        }
        if (!feof($handle)) {
            echo "Unexpected fgets() fail.\n";
        }
        fclose($handle);
    }

    return $cmu_dict;
}

/**
 * Returns the specified stanza-formatted file as a PHP array of stanza strings.
 *
 * @param $path
 * @return array
 */
function read_stanza_file($path) {
    $stanzas = array();
    $handle = fopen($path, 'r');
    if ($handle) {
        $stanza = '';
        $i = 0;
        while (($buffer = fgets($handle, 4096)) !== false) {
            if (starts_with('---', $buffer)) {
                continue;
            }
            $stanza .= $buffer;
            $i++;
            if ($i % 8 === 0) {
                $stanzas[] = rtrim($stanza);
                $stanza = '';
            }
        }
        if (!feof($handle)) {
            echo "Unexpected fgets() fail.\n";
        }
        fclose($handle);
    }

    return $stanzas;
}

if (PHP_SAPI === 'cli') {

    $positive_stanza = read_stanza_file(__DIR__ . '/stanza-positives.txt');
    echo 'Read ' . count($positive_stanza) . ' positive stanza(s).' . "\n";

    $negative_stanza = read_stanza_file(__DIR__ . '/stanza-negatives.txt');
    echo 'Read ' . count($negative_stanza) . ' negative stanza(s).' . "\n";

    foreach ($positive_stanza as $i => $stanza) {
        $fitness = ottava_rima_fitness($stanza);
        echo "Positive stanza $i " . ($fitness === 160 ? "is" : "is NOT") . " ottava rima.\n";
    }
    foreach ($negative_stanza as $i => $stanza) {
        $fitness = ottava_rima_fitness($stanza);
        echo "Negative stanza $i " . ($fitness === 160 ? "is" : "is NOT") . " ottava rima.\n";
    }

}
