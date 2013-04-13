<?php

class HTMLPurifier_Filter_FlashObject extends HTMLPurifier_Filter
{

    public $name = 'FlashObject';
    
    protected $allowFullScreen_regex = '#<param name="allowFullScreen"[^>]+/>#s'; 
    protected $allowScriptAccess_regex = '#<param name="allowScriptAccess"[^>]+/>#s'; 
    protected $allowNetworking_regex = '#<param name="allowNetworking"[^>]+/>#s'; 

    protected $config;
    /*public function preFilter($html, $config, $context) {
       // $html = "xxx";
        return $html;
    }*/

    public function postFilter($html, $config, $context) {
        $this->config = $config;
        $post_regex = '#<object[^>]+>.*?</object>#s';
        return preg_replace_callback($post_regex, array($this, 'objectCb'),$html);
    }

    
    protected function objectCb($matches){
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
