<?php

class SimpleLocalizer
{
    private $clientLang;
    private $l10n;

    public function getClientLang() {
        return $this->clientLang;        
    }
    
    public function translate($id) {
        return $this->l10n[$id];
    }
    
    public function SimpleLocalizer($dataDirectory) {

        //
        // Default to English for lack of anything better
        //
        $this->clientLang = 'en-us';

        //
        // See if we can find anything better
        //
        $matches = array();
        if (array_key_exists('HTTP_ACCEPT_LANGUAGE',$_SERVER)) {
            setlocale(LC_ALL,'en-US');
            if (preg_match('/(en-US|fr-FR|de-DE|it-IT|es-ES|sv-SV|sv-SE|nl-NL)/i',$_SERVER['HTTP_ACCEPT_LANGUAGE'],$matches)) {
                $this->clientLang = strtolower($matches[1]);
            }
        }

        //
        // Pull in all of the strings we need
        //
        $lang2file = array (
            "es-es" => "es-ES.xml",
            "en-us" => "en-US.xml",
            "de-de" => "de-DE.xml",
            "fr-fr" => "fr-FR.xml",
            "it-it" => "it-IT.xml",
            "nl-nl" => "nl-NL.xml",
            "sv-sv" => "sv-SE.xml",
            "sv-se" => "sv-SE.xml",
                            );
        
        $xml_parser   = xml_parser_create();
        $l10nValues   =  array();
        $l10nIndexes  = array();
        $l10nContents = file_get_contents("$dataDirectory/" . $lang2file[$this->getClientLang()]);
        
        if (!xml_parse_into_struct($xml_parser, $l10nContents, $l10nValues, $l10nIndexes)) {
            
            die(sprintf("l10n: XML error: %s at line %d",
                        xml_error_string(xml_get_error_code($xml_parser)),
                        xml_get_current_line_number($xml_parser)));
            
        } else {
            
            foreach ($l10nIndexes["STRING"] as $l10nStringIndex) {
                $this->l10n[$l10nValues[$l10nStringIndex]["attributes"]["ID"]] = $l10nValues[$l10nStringIndex]["value"];
            }            
        }
        
        xml_parser_free($xml_parser);
    }

}

?>