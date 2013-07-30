<?php

class HTMLPurifier_Filter_Linkify extends HTMLPurifier_Filter
{

    public $name = 'Linkify';
    
    protected $config;

    public function postFilter($html, $config, $context) {
        $this->config = $config;
        $doc = new DOMDocument();
        $doc->loadHTML($html);  
        $doc->strictErrorChecking = false;
        libxml_use_internal_errors(true);
        $xpath = new DOMXPath($doc);
        $textnodes = $xpath->query('//text()[not(ancestor::a) and normalize-space()]');
        foreach($textnodes as $node){
            $parent = $node->parentNode;
            $v = $node->nodeValue;
            $a = $doc->createElement('a');
            $a->nodeValue = $v;
            $a->setAttribute('href',$v);
            $parent->replaceChild($a,$node);
        }
        $b = $doc->getElementsByTagName('body')->item(0);
        //print_r($token_list); */
        return $doc->saveHtml($b->firstChild);
    }

    protected function textCb($matches){
        $text = $matches[1];

        return preg_replace_callback('#((?:https?|ftp)://[^\s\'"<>()]+)#S', array($this,'linkCb'),$text);
    }
    
    protected function linkCb($matches){
        print_r($matches);
        return "xx";
        $obj_regex = '#(<object[^>]+>)(.*?)(</object>)#s';
        $obj_html = $matches[0];

        //check allowScriptAccess
        $ret = preg_match($this->allowScriptAccess_regex,$matches[0]);
        if(!$ret){
            //add allowScriptAccess
            $obj_html = preg_replace_callback($obj_regex,array($this, 'addScriptAccess'),$obj_html);
        }

        //check allowNetworking
        $ret = preg_match($this->allowNetworking_regex,$matches[0]);
        if(!$ret){
            //add allowNetworking
            $obj_html = preg_replace_callback($obj_regex,array($this, 'addNetworking'),$obj_html);
        }

        //check allowFullScreen
        $ret = preg_match($this->allowFullScreen_regex,$matches[0]);
        if(!$ret){
            //add allowFullScreen
            $obj_html = preg_replace_callback($obj_regex,array($this, 'addFullScreen'),$obj_html);
        }

        $param_regex = '#<param[^>]+/>#s';
        
        return preg_replace_callback($param_regex,array($this, 'paramCb'),$obj_html);
    }

    protected function addScriptAccess($matches){
        $to_add = '<param name="allowScriptAccess" value="never" />';
        return $matches[1].$to_add.$matches[2].$matches[3];

    }

    protected function addNetworking($matches){
        $to_add = '<param name="allowNetworking" value="internal" />';
        return $matches[1].$to_add.$matches[2].$matches[3];

    }


    protected function addFullScreen($matches){
        $allow = $this->config->get('HTML.FlashAllowFullScreen');
        if($allow){
            $to_add = '<param name="allowFullScreen" value="true" />';
            return $matches[1].$to_add.$matches[2].$matches[3];
        }
        else {
            return $matches[0];
        }

    }

    protected function paramCb($matches){
        $ret = preg_match($this->allowFullScreen_regex,$matches[0]);
        $html = $matches[0];
        if($ret){
            $allow = $this->config->get('HTML.FlashAllowFullScreen');
            if($allow){
                $html = '<param name="allowFullScreen" value="true" />';
            }
            else{
                $html = '';
            }
        }

        $ret = preg_match($this->allowScriptAccess_regex,$matches[0]);
        if($ret){
            $html = '<param name="allowScriptAccess" value="never" />';
        }

        $ret = preg_match($this->allowNetworking_regex,$matches[0]);
        if($ret){
            $html = '<param name="allowNetworking" value="internal" />';
        }

        return $html;
    }
    protected function armorUrl($url) {
        return str_replace('--', '-&#45;', $url);
    }

}

// vim: et sw=4 sts=4
