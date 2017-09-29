<?php

/**
 * 	Prepare reading of SQL dump file and executing SQL statements
 * 		@param $sql_dump_file
 */
function db_install($sql_dump_file)
{
    global $error_mg;
    global $username;
    global $password;
    global $database_prefix;
    global $password_encryption;
    global $db;

    $sql_array = array();
    $query     = "";

    // get  sql dump content
    $sql_dump = file($sql_dump_file);

    // replace database prefix if exists
    $sql_dump = str_replace("<DB_PREFIX>", $database_prefix, $sql_dump);

    // add ";" at the end of file to catch last sql query
    if (substr($sql_dump[count($sql_dump) - 1], -1) != ";")
    {
        $sql_dump[count($sql_dump) - 1] .= ";";
    }

    // encode connection, server, client etc.	
    if (EI_USE_ENCODING)
    {
        $db->SetEncoding(EI_DUMP_FILE_ENCODING, EI_DUMP_FILE_COLLATION);
    }

    foreach ($sql_dump as $sql_line)
    {
        $tsl = trim(utf8_decode($sql_line));
		$tsl = trim($sql_line);
        if (($sql_line != "") && (substr($tsl, 0, 2) != "--") && (substr($tsl, 0, 1) != "?") && (substr($tsl, 0, 1) != "#"))
        {
            $query .= $sql_line;
            if (preg_match("/;\s*$/", $sql_line))
            {
                if (strlen(trim($query)) > 5)
                {
                    if (!@$db->Query($query))
                    {
                        return false;
                    }
                }
                $query = "";
            }
        }
    }
    return true;
}

/**
 * 	Returns language key
 * 		@param $key
 */
function lang_key($key)
{
    global $arrLang;
    $output = "";

    if (isset($arrLang[$key]))
    {
        $output = $arrLang[$key];
    }
    else
    {
        $output = str_replace("_", " ", $key);
    }
    return $output;
}

/**
 * 	Remove bad chars from input
 * 	  	@param $str_words - input
 * */
function prepare_input($str_words, $escape = false, $level = "high")
{
    $found = false;
    if ($level == "low")
    {
        $bad_string = array("drop", ";", "--", "insert", "xp_", "%20union%20", "/*", "*/union/*", "+union+", "load_file", "outfile", "document.cookie", "onmouse", "<script", "<iframe", "<applet", "<meta", "<style", "<form", "<body", "<link", "_GLOBALS", "_REQUEST", "_GET", "_POST", "include_path", "prefix", "ftp://", "smb://", "onmouseover=", "onmouseout=");
    }
    else if ($level == "medium")
    {
        $bad_string = array("select", "drop", ";", "--", "insert", "xp_", "%20union%20", "/*", "*/union/*", "+union+", "load_file", "outfile", "document.cookie", "onmouse", "<script", "<iframe", "<applet", "<meta", "<style", "<form", "<body", "<link", "_GLOBALS", "_REQUEST", "_GET", "_POST", "include_path", "prefix", "ftp://", "smb://", "onmouseover=", "onmouseout=");
    }
    else
    {
        $bad_string = array("select", "drop", ";", "--", "insert", "xp_", "%20union%20", "/*", "*/union/*", "+union+", "load_file", "outfile", "document.cookie", "onmouse", "<script", "<iframe", "<applet", "<meta", "<style", "<form", "<img", "<body", "<link", "_GLOBALS", "_REQUEST", "_GET", "_POST", "include_path", "prefix", "http://", "https://", "ftp://", "smb://", "onmouseover=", "onmouseout=");
    }
    for ($i = 0; $i < count($bad_string); $i++)
    {
        $str_words = str_replace($bad_string[$i], "", $str_words);
    }

    if ($escape)
    {
        $str_words = mysql_real_escape_string($str_words);
    }

    return $str_words;
}

function getInstallHost()
{
    /*
    if ($_SERVER["SERVER_PORT"] != "80")
    {
        return $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"];
    }
    */

    return $_SERVER["SERVER_NAME"];
}

function getInstallPath()
{
    $pageURL = $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];

    // remove install folder
    $pageUrlExp = explode("/install/", $pageURL);

    return $pageUrlExp[0];
}

function genRandomString($length = 16)
{
    $chars ="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
    $final_rand='';
    for($i=0;$i<$length; $i++)
    {
        $final_rand .= $chars[rand(0,strlen($chars)-1)];
 
    }
    return $final_rand;
}