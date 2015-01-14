<?php
use Symfony\Component\Yaml\Yaml;

require_once(dirname(__FILE__) . "/../vendor/autoload.php");

$dataDir = $argv[1];

$config = Yaml::parse(file_get_contents($dataDir . "/config.yml"));

$sourceTable = $config["input"]["tables"][0]["source"];
$destinationTable = $config["output"]["tables"][0]["destination"];;
$primaryKeyColumn = $config["primary_key_column"];
$dataColumn = $config["data_column"];
$stringLength = $config["string_length"];

$dataColumnIndex = 0;
$primaryKeyColumnIndex = 0;

function mb_str_split($str, $l=0) {
    if ($l > 0) {
        $ret = array();
        $len = mb_strlen($str, "UTF-8");
        for ($i = 0; $i < $len; $i += $l) {
            $ret[] = mb_substr($str, $i, $l, "UTF-8");
        }
        return $ret;
    }
    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}

if (!file_exists($dataDir . "/in/tables/{$sourceTable}.csv")) {
	print "File 'data/in/tables/{$sourceTable}.csv' not found.";
	exit(1);
}

$fhIn = fopen($dataDir . "/in/tables/{$sourceTable}.csv", "r");
$fhOut = fopen($dataDir . "/out/tables/{$destinationTable}.csv", "w");

$header = fgetcsv($fhIn, 10000);

if (!in_array($primaryKeyColumn, $header)) {
	print "Primary key column '{$primaryKeyColumn}' not found in table {$sourceTable}.";
	exit(1);
}

if (!in_array($dataColumn, $header)) {
	print "Column '{$dataColumn}' not found in table.";
	exit(1);
}

$primaryKeyColumnIndex = array_search($primaryKeyColumn, $header);
$dataColumnIndex = array_search($dataColumn, $header);

fputcsv($fhOut, array($primaryKeyColumn, $dataColumn, "row_number"));

$rows = 0;
while ($row = fgetcsv($fhIn)) {
	$rows++;
	$parts = mb_str_split($row[$dataColumnIndex], $stringLength);
	foreach($parts as $key => $part) {
		fputcsv($fhOut, array($row[$primaryKeyColumnIndex], $part, $key));
	}
}
fclose($fhIn);
fclose($fhOut);

print "Processed {$rows} rows.";
exit(0);