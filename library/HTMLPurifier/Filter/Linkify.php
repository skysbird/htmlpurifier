<?php

class HTMLPurifier_Filter_Linkify extends HTMLPurifier_Filter
{

    public $name = 'Linkify';

    protected $host_list = array();
    protected $config;


    protected function is_safe($uri){
       $rel = false;
       foreach($this->host_list as $host){
           if (fnmatch($host,$uri)) { 
                $rel = true;
                break;
           }
       }

       return $rel;
    }

    public function postFilter($html, $config, $context) {
        
        $this->host_list = $config->get('Linkify.Hostlist');
        $this->config = $config;
        $doc = new DOMDocument();
        $doc->loadHTML($html);  
        $xpath = new DOMXPath($doc);
        $textnodes = $xpath->query('//text()[not(ancestor::a) and normalize-space()]');
        foreach($textnodes as $node){
            $v = $node->nodeValue;
            if($this->is_safe($v)){
                $parent = $node->parentNode;
                $a = $doc->createElement('a');
                $a->nodeValue = $v;
                $a->setAttribute('href',$v);
                $parent->replaceChild($a,$node);
            }
        }
        $b = $doc->getElementsByTagName('body')->item(0);
        //print_r($token_list); */
        return $doc->saveHtml($b->firstChild);
    }


}

// vim: et sw=4 sts=4
