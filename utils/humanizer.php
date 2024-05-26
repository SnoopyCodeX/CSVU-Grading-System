<?php

/**
 * Converts a long number into a 
 * human readable format.
 * EG: 18500 -> 18.5k
 *
 * @source https://www.sourcecodester.com/shorten-longlarge-numbers-using-php.html
 * @param int $num The number to format
 * @return string
 */
function humanizeNumber($num) {
    if($num >= 1000) {
        $x = round($num);
        $x_number_format = number_format($x);
        $x_array = explode(',', $x_number_format);
        $x_parts = array('k', 'M', 'B', 'T', 'q', 'Q', 's', 'S', 'O', 'N', 'd');

        $x_count_parts = count($x_array) - 1;
        $x_display = $x;
        $x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
        $x_display .= $x_parts[$x_count_parts - 1];

        return $x_display;
    }

    return $num;
}

/**
 * Function to remove duplicate entries from an 
 * array of objects based on a unique key
 *
 * @param array $array The array containing duplicate objects
 * @param string $uniqueKey The uniqueKey of each objects
 * @return array
 */
function removeDuplicates($array, $uniqueKey) {
    // Extract unique keys from objects
    $uniqueKeys = array_map(function($obj) use ($uniqueKey) {
        return $obj[$uniqueKey];
    }, $array);

    // Remove duplicate unique keys
    $uniqueKeys = array_unique($uniqueKeys);

    // Rebuild array of objects
    $result = [];
    foreach ($uniqueKeys as $key) {
        foreach ($array as $obj) {
            if ($obj[$uniqueKey] === $key) {
                $result[] = $obj;
                break; // Move to the next unique key
            }
        }
    }

    return $result;
}

?>