<?php

// Get command line options
$options = getopt('l:s::hrv:e:t:o:p:');
if (empty($options) || isset($options['h'])) {
    echo 'Usage: ' . $argv[0] . '-l<location> -o<output file> -p<path to rips> >-s<include subdirs, 1 or 0>, -r<regex of file patterns> -v<verbosity level 1-5> -e<attack vector> -t<tree style 1 = bottom-up, 2 = top-down>' . "\n
    vector types:
    server - All server side
    code = - Code Evaluation
    exec = - Command Execution
    connect = - Header Injection
    file_read = - File Disclosure
    file_include = - File Inclusion
    file_affect = - File Manipulation
    ldap = - LDAP Injection
    database = - SQL Injection
    xpath = - XPath Injection
    client = Cross-Site Scripting
    all = All
    unserialize = Unserialize / POP\n";
    exit();
}
$_POST = array(
    'loc' => @$options['l'],
    'subdirs' => @$options['s'],
    'ignore_warning' => false,
    'search' => @$options['r'],
    'verbosity' => @$options['v'],
    'vector' => @$options['e'],
    'treestyle' => @$options['t']
);

// Run RIPS, save the output
ob_start();
if (is_dir($options['p'])) {
    require_once(realpath($options['p']) . '/main.php');
} else {
    require_once(realpath(dirname($options['p'])) . '/main.php');
}
$output = ob_get_clean();

// Write the output to a file
$fp = fopen($options['o'], 'w');
fwrite($fp, '<html>
<head>
	<title>RIPS - A static source code analyser for vulnerabilities in PHP scripts</title>
	<style type="text/css">
    html, body, div, span, applet, object, iframe,
    h1, h2, h3, h4, h5, h6, p, blockquote, pre,
    a, abbr, acronym, address, big, cite, code,
    del, dfn, em, img, ins, kbd, q, s, samp,
    small, strike, strong, sub, sup, tt, var,
    b, u, i, center,
    dl, dt, dd, ol, ul, li,
    fieldset, form, label, legend,
    table, caption, tbody, tfoot, thead, tr, th, td,
    article, aside, canvas, details, embed,
    figure, figcaption, footer, header, hgroup,
    menu, nav, output, ruby, section, summary,
    time, mark, audio, video {
        font-family: sans-serif;
        background-color: white;
        color: black;
    }

    div.menu {
        display: none;
    }
    div.menushade {
        display: none;
    }
    div.stats {
        background-color: white;
        color: black;
        position: relative;
        margin-bottom: 15px;
    }
    div#window1,
    div#window2,
    div#window3,
    div#window4,
    div#window5 {
        display: none;
    }
    div.vulnblock {
        background-color: white !important;
        color: white;
    }
    div.codebox {
        margin-bottom: 1px;
        background-color: white;
    }
    div.vulnblock span {
        color: black;
    }
    div.vulnblock span.phps-t-constant-encapsed-string,
    div.vulnblock span.phps-t-encapsed-and-whitespace,
    div.vulnblock span.phps-t-comment,
    div.vulnblock span.phps-t-ml-comment,
    div.vulnblock span.phps-t-doc-comment {
        color: #999999;
    }
    div.buttonbox {
        display: none;
    }
    div.help,
    div.fileico,
    div.minusico,
    div.exploit {
        display: none;
    }
    input.button[value=x] {
        display: none;
    }
    span.filename {
        font-weight: bold;
    }
	</style>
</head>
<body>
<div id="result">
');
fwrite($fp, $output);
fwrite($fp, '</div></body></html>');
fclose($fp);
