<?php
session_start();
if (!array_key_exists('text', $_POST)) {
    exit("Need a POST value.");
}

$python = "/usr/local/bin/python3.9";

$extensions = array(
  "dependencies" => "_dependencies.gv",
  "diagram" => ".mld",
  "markdown_data_dict" => "_data_dict.md",
  "html" => ".html",
  "html_verbose" => "_verbose.html",
  "json" => ".json",
  "latex" => ".tex",
  "markdown" => ".md",
  "markdown_verbose" => "_verbose.md",
  "mysql" => "_mysql.sql",
  "oracle" => "_oracle.sql",
  "postgresql" => "_postgresql.sql",
  "sqlite" => "_sqlite.sql",
  "text" => ".txt",
  "txt2tags" => ".t2t",
);
$flex = array(
  "désactivée" => 0,
  "peu perceptible" => 0.25,
  "faible" => 0.5,
  "normale" => 0.75,
  "forte" => 1.0,
  "très prononcée" => 1.25,
);

// Make a folder for this user
$path = str_replace(":", "_", __DIR__."/sessions/" . $_SERVER['REMOTE_ADDR'] . "-" . session_id()) ; // prevent the automatic substitution of : by / on Mac OS X (IPV6 syntax)
file_exists($path) or mkdir($path) or die('{"err": "PHP: Failed to create user folder."}');
chdir($path);

$postId = md5(serialize($_POST));

// $php_log = fopen("php.log", 'w') or die('{"err": "PHP: Can\'t open log file."}');
// fwrite($php_log, $postId);
// $trace = 0;
// fwrite($php_log,$_POST['state']);
// fwrite($php_log,$trace++);

$title = preg_replace("/[^- _a-zA-Z0-9.]/","",$_POST['title']); # double-check js validation
$title = preg_replace("/^ *$/","Sans titre",$title); # double-check js validation
$_POST['input'] = "{$title}.mcd";
$_POST['relations'] = array_key_exists('relations', $_POST) ? $_POST['relations'] : array();

// fwrite($php_log,"sql" . $_POST['SQL_dialect'] . "\n");
if ($_POST['SQL_dialect']) {
    $_POST['relations'][] = strtolower($_POST['SQL_dialect']);
};
// fwrite($php_log, "relations:" . print_r($_POST['relations'], true) . "\n");
// fwrite($php_log, "state:" . $_POST['state'] . "\n");
if ($_POST['state']=="moved") {
    $geo = json_decode(file_get_contents("{$title}_geo.json"),true);
    $geo['width'] = intval($_POST['width']);
    $geo['height'] = intval($_POST['height']);
    foreach ($geo["cx"] as $i => $value) {
        $geo["cx"][$i] = array($value[0],intval($_POST["cx".$i]));
        $geo["cy"][$i] = array($value[0],intval($_POST["cy".$i]));
    };
    foreach ($geo["shift"] as $i => $value) {
        $geo["shift"][$i] = array($value[0],floatval($_POST["shift".$i]));
    };
    foreach ($geo["ratio"] as $i => $value) {
        $geo["ratio"][$i] = array($value[0],floatval($_POST["ratio".$i]));
    };
    $chan = fopen("{$title}_geo.json", 'w') or die('{"err": "PHP: Can\'t open geo file."}');
    fwrite($chan, json_encode($geo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fclose($chan);
  }
else {
  // Clean the directory up
  foreach (glob("*.*") as $filename) {
     unlink($filename);
  };
    // Create the MCD text file
    $chan = fopen($_POST['input'], 'w') or die('{"err": "PHP: Can\'t open MCD file."}');
    $_POST['text'] = str_replace('"','',$_POST['text']); # double-check js validation
    $_POST['text'] = str_replace('`','',$_POST['text']); # double-check js validation
    $_POST['text'] = str_replace("\\'","'",$_POST['text']); # disable http://en.wikipedia.org/wiki/Magic_quotes
    fwrite($chan, $_POST['text']);
    fclose($chan);

    // Prepare the contents of the options file
    unset($_POST['text']);
    unset($_POST['state']);
    $_POST["guess_title"] = ($_POST["guess_title"] == "true");
    $_POST['language'] = 'fr';
    $_POST['encodings'] = array("utf8");
    if ($_POST['disambiguation'] == "notes et numéros") {
        $_POST['disambiguation'] = "notes";
    } else {
        $_POST['disambiguation'] = "numbers_only";
    }
    $_POST['flex'] = $flex[$_POST['flex']];

    // Write it
    $chan = fopen("params.json", 'w') or die('{"err": "PHP: Can\'t open \'params.json\' file."}');
    fwrite($chan, json_encode($_POST));
    fclose($chan);
  };

  // Launch the script
$command_line = "~/.local/bin/mocodo 2>&1 >/dev/null";
$out = array();
// die('{"err": "' . $command_line . '"}');

// fwrite($php_log, "command line" . $command_line . "\n");
// fclose($php_log);

exec($command_line,$out);
// $mocodo_log = fopen("mocodo.log", 'w') or die('{"err": "PHP: can\'t open \'mocodo.log\' file"}');
// fwrite($mocodo_log, implode("\n",$out));
// fclose($mocodo_log);
if (!empty($out)) {
    echo json_encode(array("err" => implode("\n",$out)));
    exit();
};

// may update the title
$title_filename = "{$title}_new_title.txt";
if (file_exists($title_filename)) {
  $title = file_get_contents($title_filename) or die('{"err": "PHP: Can\'t read new title."}');
  rename($_POST['input'], "{$title}.mcd");
  unlink($title_filename);
};

// make the archive
$zip = new ZipArchive();
if ($zip->open("{$title}.zip", ZIPARCHIVE::CREATE)!==TRUE) {
    die('{"err": "PHP: Can\'t open <{$title}.zip>\n"}');
};

$zip->addFile("{$title}_geo.json");
$zip->addFile("{$title}.mcd");
$zip->addFile("{$title}.svg");
// The following instructions fail silently if the (optional) files do not exist
$zip->addFile("{$title}_static.svg");
$zip->addFile("{$title}.png");
$zip->addFile("{$title}.pdf");
foreach ($_POST['relations'] as $key) {
    $ext = $extensions[$key];
    $zip->addFile("{$title}{$ext}");
};
$zip->close();


// return the response
$svg = file_get_contents("{$title}.svg");
$count = 1;
$result = array(
    "svg" => str_replace('stroke="none" stroke-width="0"', 'stroke="#808080" stroke-width="1" stroke-dasharray="2,2"', $svg, $count),
    "geo" => file_get_contents("{$title}_geo.json"),
    "zip" => "{$title}.zip",
    "mld" => array(),
  "title" => $title,
);
foreach ((array)$_POST['relations'] as $key) {
    $ext = $extensions[$key];
    $str = file_get_contents("{$title}{$ext}");
    $str = str_replace('<','&lt;',$str);
    $result["mld"][] = array($key,$str);
};
echo json_encode($result);
?>
