<?php
  ini_set('display_errors', 1);

  $configUrl = "switch.config";
  $localhost = "corpus3.aac.ac.at";

  function GetNodeValue($node, $tagName)
  {
     $list = $node->getElementsByTagName($tagName);
     if ($list->length != 0)
     {
       $value = $list->item(0);
       return $value->nodeValue;
     }
     return "";
  }

  function GetConfig($context)
  {
    global $configUrl;
    $doc = new DOMDocument;
    $doc->Load($configUrl);

    $xpath = new DOMXPath($doc);
    $query = '//item';
    $entries = $xpath->query($query);

    foreach ($entries as $entry)
    {
       $name = GetNodeValue($entry, "name");
       if ($name == $context)
       {
         //echo "Name: $name - ";

         $type = GetNodeValue($entry, "type");
         //echo "Type: $type - ";

         $uri = GetNodeValue($entry, "uri");
         //echo "Uri: $uri\n";

         $style = GetNodeValue($entry, "style");
         //echo "Style: $style\n";

         return array("name" => $name, "type" => $type, "uri" => $uri, "style" => $style);
       }
    }
  }

  //header("Content-Type: text/plain");

  $hstr = $_GET["x-context"];

  if (isset($_GET['x-format']))
    $format = trim($_GET['x-format']);
  else
    $format = "";

  $params = $_SERVER["REQUEST_URI"];
  $params = strstr($params, "?");
  $params = str_replace("x-context=" . $hstr, "", $params);

  $sAry = array("?&", "&&");
  $rAry = array("?", "&");

  $params = str_replace($sAry, $rAry, $params);
  //print "Params: $params\n";


	 // add default params
	 $params = $params."&version=1.2";

  $context = explode(",", $hstr);
  //print_r($context);

  foreach($context as $item)
  {
    $ary = GetConfig($item);
    //print_r($ary);

    $uri = str_replace($localhost, "localhost",  $ary["uri"]);

    if ($ary['type'] == "fcs")
    {
      if ($params == "")
        $params = "?x-context=" . $item;
      else
        $params .= "&x-context=" . $item;
    }
    $fileName = $uri . $params;

    $pos = stripos($format, "html");
    //print "StriPos: $pos";

	   //depending on x-format parameter decide if to transform the result (into html), or leave it as raw xml
    if ($pos !== FALSE)
    {
      $style = str_replace($localhost, "localhost",  $ary["style"]);
      //print $fileName;

      if (file_exists($style))
      {
        $xslDoc = new DOMDocument();
        $xslDoc->load($style);

        if (file_exists($fileName))
        {
          $xmlDoc = new DOMDocument();
          $xmlDoc->load($fileName);

          $proc = new XSLTProcessor();
          $proc->importStylesheet($xslDoc);
         	$proc->setParameter('', 'format', $format);
          header("content-type: text/html; charset=UTF-8");
          echo $proc->transformToXML($xmlDoc);
        }
        else
        {
          print "$fileName not found!";
        }
      }
      else
      {
        print "$style not found!";
      }
    }
    else if (stripos($format, "xsltproc") !== FALSE)
    {
    	 print "XSLTPROC-testing";
    	 print "hasExsltSupport:".$proc->hasExsltSupport;
      //print $xslDoc->saveXML();
    }
    else if (stripos($format, "xsl") !== FALSE)
    {
    	 // this option is more or less only for debugging (to see the xsl used)
    	 $style = str_replace($localhost, "localhost",  $ary["style"]);

    	 header ("content-type: text/xml; charset=UTF-8");
    	 readfile($style);

    	 //$xslDoc = new DOMDocument();
      //$xslDoc->load($style);
      //print $xslDoc->saveXML();
    }
    else
    {
      header ("content-type: text/xml; charset=UTF-8");
      $fileName = $uri . $params;
      if (file_exists($fileName))
      {
        readfile($fileName);
      }
      else
      {
        print "<message>uri or script not found!</message>";
      }
    }
  }
?>