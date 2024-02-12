#!/usr/bin/env php
<?php
/*

git clone https://github.com/php/doc-en
./gen-php-api.php ./doc-en/ > php83.api

*/

if (!isset($argv[1])) {
  echo "path to doc not set\n";
  exit(1);
}

if (!is_dir($argv[1])) {
  echo "directory '" . $argv[1] . "' not found\n";
  exit(1);
}

function readXML($filename){
    $xml_string = implode("", file($filename));
    $xml_string = str_replace("&", "ref", $xml_string);
    return simplexml_load_string($xml_string);
}
function xml2str($xml){
    $str = str_replace("[[entity]]", "&", (string)$xml);
    $str = iconv("UTF-8", "WINDOWS-1251", $str);
    return $str;
}

$pattern = str_replace("//", "/", $argv[1] . "/reference/*/constants.xml");
$files = glob($pattern);
$const = array();
foreach($files as $file) {
  $xml = readXML($file);
  if (count($xml->variablelist) > 0) {
    foreach($xml->variablelist->varlistentry as $value) {
      $const["" . $value->term->constant] = "" . $value->term->constant;
    }
  }
}

$pattern = str_replace("//", "/", $argv[1] . "/reference/*/functions/*.xml");
$files = glob($pattern);

$functions = array();
foreach($files as $file) {
  $xml = readXML($file);
  $function = array();
  if (isset($xml->refnamediv->refname)) {
    $function["name"] = "" . $xml->refnamediv->refname;
  }
  if (isset($xml->refnamediv->refpurpose)) {
    $function["purpose"] = trim(str_replace("\n", " ", "" . $xml->refnamediv->refpurpose));
    if (isset($xml->refnamediv->refpurpose->function)) {
      $function["alias"] = "" . $xml->refnamediv->refpurpose->function;
    } else {
      $function["alias"] = "";
    }
  }
  $parameter = array();
  $return = array();
  foreach($xml->refsect1 as $refsect1) {
    $role = "" . $refsect1["role"];
    switch($role) {
      case "description":
        // print_r($refsect1);
        if (isset($refsect1->methodsynopsis->methodparam)) {
          foreach($refsect1->methodsynopsis->methodparam as $param) {
            $data = $param->type . " \$" . $param->parameter;
            if (isset($param->initializer)) {
              if (isset($param->initializer->constant)) {
                $data .= " = " . $param->initializer->constant;
              } else {
                $data .= " = ". $param->initializer;
              }
            }
            $parameter[] = $data;
          }
        }

        if (isset($refsect1->methodsynopsis->type)) {
          if ($refsect1->methodsynopsis->type["class"]) {
            switch($refsect1->methodsynopsis->type["class"]) {
              case "union":
                foreach($refsect1->methodsynopsis->type->type as $type) {
                  $return[] = "" . $type;
                }
                break;
            }
          } else {
            $return = array("" . $refsect1->methodsynopsis->type);
          }
        }
        break;
      case "parameters":
        break;
      case "returnvalues":
        break;
      case "errors":
        break;
      case "examples":
        break;
      case "seealso":
        break;
      default:
        // echo $role . "\n";
        break;
    }
  }
  $function["parameter"] = $parameter;
  $function["return"] = $return;
  $functions[$function["name"]] = $function;
}

foreach($const as $c) {
  echo $c . "\n";
}
foreach($functions as $function) {
  if ($function["alias"] != "") {
    if (isset($functions[$function["alias"]])) {
      $function["parameter"] = $functions[$function["alias"]]["parameter"];
      $function["return"] = $functions[$function["alias"]]["return"];
      $function["purpose"] = $functions[$function["alias"]]["purpose"];
    }
  }
  echo $function["name"] . " ( " . implode(", ", $function["parameter"]) . "):" . implode("|", $function["return"]) . " | " . $function["purpose"];
  echo "\n";
}
