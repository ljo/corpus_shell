<?php

error_reporting(E_ALL);

 //load configuration
 include "config.php";

 //load database and user data
 include $dbConfigFile;

 if (isset($_GET['x-type']) && trim($_GET['x-type']) == "fcs")
   $responseTemplate = $responseTemplateFcs;

 //required values for the templates
 $recordSchema = "http://clarin.eu/fcs/1.0/Resource.xsd";
 $version = "1.2";

 function diagnostics($dgId, $dgDetails)
 {
    global $diagnosticsTemplate;
    global $version;
    global $vlibPath;

    //TODO: this needs to be based on diagnostics-list:
    //http://www.loc.gov/standards/sru/resources/diagnostics-list.html
    $diagnosticMessage = "Error";
    $diagnosticId = $dgId;
    $diagnosticDetails = $dgDetails;

    require_once $vlibPath;

   	$tmpl = new vlibTemplate($diagnosticsTemplate);

   	$tmpl->setvar('version', $version);
   	$tmpl->setvar('diagnosticId', $diagnosticId);
   	$tmpl->setvar('diagnosticMessage', $diagnosticMessage);
   	$tmpl->setvar('diagnosticDetails', $diagnosticDetails);

   	$tmpl->pparse();
 }

 function explain()
 {
    global $explainTemplate;
    global $vlibPath;

    require_once $vlibPath;

   	$tmpl = new vlibTemplate($explainTemplate);
   	$tmpl->pparse();
 }

 function decodecharrefs($str)
 {
   //str_replace("alt","neu","Zeichenkette")
   $str = str_replace("#9#", ";", $str);
   $str = str_replace("#8#", "&#", $str);
   $str = str_replace("&#amp;", "&amp;", $str);
   return $str;
 }

 function search($query, $glossTable, $startRecord, $maximumRecords)
 {
    global $responseTemplate;
    global $version;
    global $recordSchema;
    global $recordPacking;

    global $server;
    global $user;
    global $password;
    global $database;

    global $vlibPath;

    require_once $vlibPath;

    $db = mysql_connect($server, $user, $password);
    if (!$db)
      diagnostics('MySQl Connection Error', 'Failed to connect to database: ' . mysql_error());
    mysql_select_db($database, $db);

    $sqlstr = "SELECT DISTINCT b.id, b.entry FROM $glossTable b, " . $glossTable . "_ndx ndx ";
    $sqlstr.= "WHERE ndx.xpath LIKE '%form-lemma-orth%' AND ";

    if (strpos($query, '"') !== false)
    {
      $hstr = str_replace('"', '', $query);
      $sqlstr.= "ndx.txt='$hstr' AND ndx.id=b.id ORDER BY b.id ";
    }
    else
      $sqlstr.= "ndx.txt LIKE '%$query%' AND ndx.id=b.id ORDER BY b.id ";


    if ($maximumRecords != "0")
      $sqlstr.= "LIMIT $startRecord, $maximumRecords;";

    //print "sqlstr: '$sqlstr'";

    $result = mysql_query($sqlstr);

    $numberOfRecords = mysql_num_rows($result);

   	$tmpl = new vlibTemplate($responseTemplate);

   	$tmpl->setvar('version', $version);
   	$tmpl->setvar('numberOfRecords', $numberOfRecords);
   	$tmpl->setvar('query', $query);
   	$tmpl->setvar('baseURL', $baseURL);
   	$tmpl->setvar('nextRecordPosition', $nextRecordPosition);
   	$tmpl->setvar('res', '1');

   	$hits = array();
   	$hitsMetaData = array();
   	array_push($hitsMetaData, array('key' => 'copyright', 'value' => 'ICLTT'));
   	$hstr = "Wiktionary entry: $query";
   	array_push($hitsMetaData, array('key' => 'content', 'value' => $hstr));

    while ($line = mysql_fetch_row($result))
    {
        $id = $line[0];
        $content = $line[1];

        array_push($hits, array(
               'recordSchema' => $recordSchema,
               'recordPacking' => $recordPacking,
               'queryUrl' => $baseURL,
               'content' => decodecharrefs($content),
               'hitsMetaData' => $hitsMetaData
               ));
    }

    $tmpl->setloop('hits', $hits);
   	$tmpl->pparse();
 }

  //sru params
  if (isset($_GET['operation']) && trim($_GET['operation']) != "")
    $operation = trim($_GET['operation']);
  else
    $operation = "";

  if (isset($_GET['version']) && trim($_GET['version']) != "")
    $version = trim($_GET['version']);

  if (isset($_GET['startRecord']) && trim($_GET['startRecord']) != "")
    $startRecord = trim($_GET['startRecord']);
  else
    $startRecord = "0";

  if (isset($_GET['maximumRecords']) && trim($_GET['maximumRecords']) != "")
    $maximumRecords = trim($_GET['maximumRecords']);
  else
    $maximumRecords = "20";

  if (isset($_GET['query']) && trim($_GET['query']) != "")
    $query = trim($_GET['query']);
  else
    $query = "";

  if (isset($_GET['scanClause']) && trim($_GET['scanClause']) != "")
    $scanClause = trim($_GET['scanClause']);
  else
    $scanClause = "";

  if (isset($_GET['x-context']) && trim($_GET['x-context']) != "")
    $glossTable = trim($_GET['x-context']);
  else
    $glossTable = "";

  if (isset($_GET['recordPacking']) && trim($_GET['recordPacking']) == "xml")
    $recordPacking = trim($_GET['recordPacking']);
  else
    $recordPacking = "raw";

  header ("content-type: text/xml; charset=UTF-8");
  //print "test";

  if ($operation == "explain" || $operation == "")
    explain();
  else if ($operation == "scan")
  {
    if ($scanClause != "")
    {
      $glossTable = $scanClause;

      if ($glossTable != "")
        search("_", $glossTable, $startRecord, $maximumRecords);
      else
        diagnostics("parameter error", "The parameter 'scanClause' is not set or is empty");
    }
    else
      diagnostics("parameter error", "The parameter 'scanClause' is not set or is empty");
  }
  else if ($operation == "searchRetrieve")
  {
    if ($query != "")
    {
      if ($glossTable != "")
        search($query, $glossTable, $startRecord, $maximumRecords);
      else
        diagnostics("parameter error", "The parameter 'query' is not set or doesn't contain a search clause");
    }
    else
      diagnostics("parameter error", "The parameter 'query' is not set or is empty");
  }
  else
    diagnostics("operation unknown", "The operation '$operation' is not SRU compliant");

?>

