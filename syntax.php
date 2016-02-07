<?php
/**
 * Plugin XSLT : Perform XSL Transformation on provided XML data
 * 
 * To be run with Dokuwiki only
 *
 * Sample data provided at the end of the file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     R�mi Peyronnet  <remi+xslt@via.ecp.fr>
 */
 
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
class syntax_plugin_xslt extends DokuWiki_Syntax_Plugin {
 
    function getInfo(){
      return array(
        'author' => 'R�mi Peyronnet',
        'email'  => 'remi+xslt@via.ecp.fr',
        'date'   => '2009-03-07',
        'name'   => 'XSLT Plugin',
        'desc'   => 'Perform XSL Transformation on provided XML data',
        'url'    => 'http://people.via.ecp.fr/~remi/',
      );
    }
 
    function getType() { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort() { return 1242; }
    function connectTo($mode) { $this->Lexer->addSpecialPattern('&&XML&&\n.*\n&&XSLT&&\n.*\n&&END&&',$mode,'plugin_xslt'); }

    function handle($match, $state, $pos, &$handler)
    { 
        switch ($state) {
          case DOKU_LEXER_SPECIAL :
                $matches = preg_split('/(&&XML&&\n*|\n*&&XSLT&&\n*|\n*&&END&&)/', $match, 5);
                $data = "XML: " . $matches[1] . "\nXSLT: ". $matches[2] . "(" . $match . ")";
                $xsltproc = new XsltProcessor();
                $xml = new DomDocument;
                $xsl = new DomDocument;
                $xml->loadXML($matches[1]);
                $xsl->loadXML($matches[2]);
                $xsltproc->registerPHPFunctions();
                $xsltproc->importStyleSheet($xsl);
                $data = $xsltproc->transformToXML($xml);
                
                if (!$data) {
                    $errors = libxml_get_errors();
                    foreach ($errors as $error) {
                        $data = display_xml_error($error, $xml);
                    }
                    libxml_clear_errors();
                }                

                unset($xsltproc);
                return array($state, $data);
 
          case DOKU_LEXER_UNMATCHED :  return array($state, $match);
          case DOKU_LEXER_EXIT :       return array($state, '');
        }
        return array();
    }
    
    function render($mode, &$renderer, $data) 
    {
         if($mode == 'xhtml'){
            list($state, $match) = $data;
            switch ($state) {
              case DOKU_LEXER_SPECIAL :      
                $renderer->doc .= $match; 
                break;
 
              case DOKU_LEXER_UNMATCHED :  $renderer->doc .= $renderer->_xmlEntities($match); break;
              case DOKU_LEXER_EXIT :       $renderer->doc .= ""; break;
            }
            return true;
        }
        return false;
    }
}


function display_xml_error($error, $xml)
{
    $return  = $xml[$error->line - 1] . "\n";
    $return .= str_repeat('-', $error->column) . "^\n";

    switch ($error->level) {
        case LIBXML_ERR_WARNING:
            $return .= "Warning $error->code: ";
            break;
         case LIBXML_ERR_ERROR:
            $return .= "Error $error->code: ";
            break;
        case LIBXML_ERR_FATAL:
            $return .= "Fatal Error $error->code: ";
            break;
    }

    $return .= trim($error->message) .
               "\n  Line: $error->line" .
               "\n  Column: $error->column";

    if ($error->file) {
        $return .= "\n  File: $error->file";
    }

    return "$return\n\n--------------------------------------------\n\n";
}

/*

Sample data :

This is my list of books, maintained in XML/XSLT inside Dokuwiki.
&&XML&&

<xml>
<book>Book 1</book>
<book>Book 2</book>
</xml>

&&XSLT&&

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/TR/REC-html40">
<xsl:template match="/">List of books : <ul><xsl:apply-templates /></ul></xsl:template>
<xsl:template match="book"><li><b><xsl:apply-templates /></b></li></xsl:template>
</xsl:stylesheet>

&&END&&


*/

?>