<?php

// Get command line options
$options = getopt('l:o:h');
if (empty($options) || isset($options['h'])) {
    echo 'Usage: ' . $argv[0] . "-l<location> -o<output file>\n";
    exit();
}

// Look for readme.txt
$dir = $options['l'];
if (!file_exists($dir) || !is_dir($dir)) {
    echo "Cannot find $dir\n";
    exit();
}
$files = glob("$dir/*/readme.txt");
if (1 !== count($files)) {
    echo "Cannot find exactly one readme.txt file with this pattern: $dir/*/readme.txt\n";
    exit();
} else {
    $readme = $files[0];
}

// Post to http://wordpress.org/extend/plugins/about/validator/
$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_URL            => 'http://wordpress.org/extend/plugins/about/validator/',
    CURLOPT_POSTFIELDS     => array(
	'text'             => 1,
	'readme_contents'  => file_get_contents($readme)	
    )
));
$results = curl_exec($ch);
curl_close($ch);

// Strip out the "try again" form
require_once(realpath(dirname(__FILE__)) . '/simple_html_dom.php');
$html = str_get_html($results);
$html->find('form', 0)->outertext = '';
$html->find('h2#re-edit', 0)->outertext = '';
$html->find('a[href=#re-edit]', 0)->outertext = '';

// Write the output to a file
file_put_contents($options['o'], $html);
