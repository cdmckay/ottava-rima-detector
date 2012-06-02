<?php

/**
 * Determines whether a poem stanza is in the
 * ottava rima rhyming style (a-b-a-b-a-b-c-c).
 *
 * @param string $stanza
 * @param string $delimiter
 * @throws InvalidArgumentException
 * @return bool
 */
function is_ottava_rima($stanza, $delimiter = "\n") {
    if (!is_string($stanza)) {
        throw new InvalidArgumentException('The stanza must be a string.');
    }
    if (!is_string($delimiter)) {
        throw new InvalidArgumentException('The delimiter must be a string.');
    }

    // Separate the stanza into lines.
    $lines = explode($delimiter, $stanza);

    // Ensure there are the correct amount of lines.
    if (count($lines) !== 8) {
        return false;
    }

    // Reduce the lines into just the last word.
    $last_words = array();
    foreach ($lines as $line) {
        // Adapted from:
        // http://stackoverflow.com/questions/790596/split-a-text-into-single-words
        // This will split on a group of one or more whitespace characters, but also suck in any surrounding
        // punctuation characters. It also matches punctuation characters at the beginning or end of the string.
        // This discriminates cases such as "don't" and "he said 'ouch!'"
        $line_words = preg_split('/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', $line, -1, PREG_SPLIT_NO_EMPTY);

        if (count($line_words) === 0) {
            return false;
        }

        $last_words[] = $line_words[count($line_words) - 1];
    }

    // Determine whether the a, b, and c rhymes match.
    $a1 = $last_words[0];
    $b1 = $last_words[1];
    $a2 = $last_words[2];
    $b2 = $last_words[3];
    $a3 = $last_words[4];
    $b3 = $last_words[5];
    $c1 = $last_words[6];
    $c2 = $last_words[7];

    $a_rhyme = does_rhyme($a1, $a2) && does_rhyme($a1, $a3) && does_rhyme($a2, $a3);
    $b_rhyme = does_rhyme($b1, $b2) && does_rhyme($b1, $b2) && does_rhyme($b2, $b3);
    $c_rhyme = does_rhyme($c1, $c2);

    return $a_rhyme && $b_rhyme && $c_rhyme;
}

// has issues with new and hue
function does_rhyme_metaphone($str1, $str2) {
    $metaphone1 = metaphone($str1);
    $metaphone2 = metaphone($str2);
    return substr($metaphone1, -1) === substr($metaphone2, -1);
}

function does_rhyme($str1, $str2) {
    static $cmu_dict = array();
    if (count($cmu_dict) === 0) {
        $handle = fopen(__DIR__ . '/cmu-dict.txt', 'r');
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                list($word, $syllables) = explode('  ', $buffer);
                if (preg_match("/(.+)\(\d+\)/", $word, $matches)) {
                    $cmu_dict[$matches[1]][] = explode(' ', $syllables);
                } else {
                    $cmu_dict[$word] = array(explode(' ', $syllables));
                }
            }
            if (!feof($handle)) {
                echo "Unexpected fgets() fail.\n";
            }
            fclose($handle);
        }
        echo "Read " . count($cmu_dict) . " words to memory.\n";
    }

    $words_found = true;

    if (!array_key_exists(strtoupper($str1), $cmu_dict)) {
        //echo "Word not found: $str1.\n";
        $words_found = false;
    }

    if (!array_key_exists(strtoupper($str2), $cmu_dict)) {
        //echo "Word not found: $str2.\n";
        $words_found = false;
    }

    if (!$words_found) {
        return does_rhyme_metaphone($str1, $str2);
    }

    $pronunciations1 = $cmu_dict[strtoupper($str1)];
    $pronunciations2 = $cmu_dict[strtoupper($str2)];
    foreach ($pronunciations1 as $pronunciation1) {
        foreach ($pronunciations2 as $pronunciation2) {
            if ($pronunciation1[count($pronunciation1) - 1] === $pronunciation2[count($pronunciation2) - 1]) {
                return true;
            }
        }
    }
    return false;
}

function read_stanza_file($path) {
    $stanzas = array();

    $handle = fopen($path, 'r');
    if ($handle) {
        $stanza = '';
        $i = 0;
        while (($buffer = fgets($handle, 4096)) !== false) {
            if (substr($buffer, 0, 3) === '---') {
                continue;
            }
            $stanza .= $buffer;
            $i++;
            if ($i % 8 === 0) {
                $stanzas[] = chop($stanza);
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

$stanza_positives = read_stanza_file(__DIR__ . '/stanza-positives.txt');
echo 'Read ' . count($stanza_positives) . ' positive stanza(s).' . "\n";

$stanza_negatives = read_stanza_file(__DIR__ . '/stanza-negatives.txt');
echo 'Read ' . count($stanza_negatives) . ' negative stanza(s).' . "\n";

foreach ($stanza_positives as $stanza) {
    echo "Stanza positive is " . (is_ottava_rima($stanza) ? "true" : "false") . ".\n";
}
foreach ($stanza_negatives as $stanza) {
    echo "Stanza negative is " . (is_ottava_rima($stanza) ? "true" : "false") . ".\n";
}
