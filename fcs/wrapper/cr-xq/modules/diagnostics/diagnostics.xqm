xquery version "3.0";
module namespace diag  = "http://www.loc.gov/zing/srw/diagnostic/";

(:
  Fetch diagostic from SRU diagnostic list 
  $Id$
:)

declare variable $diag:msgs := doc('diagnostics.xml');

declare function diag:diagnostics($key as xs:string, $param as xs:string) as item()? {
    
    let $diag := 
	       if (exists($diag:msgs//diagnostic-list-item[@key eq $key])) then
	               $diag:msgs//diagnostic-list-item[@key eq $key]/diag:diagnostic
	           else $diag:msgs//diagnostic-list-item[@key eq "general-error"]/diag:diagnostic
	   return
	       <diagnostics>
	           { util:eval(util:serialize($diag,())) }
	       </diagnostics>	   
};
