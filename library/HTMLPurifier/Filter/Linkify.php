<?php

class HTMLPurifier_Filter_Linkify extends HTMLPurifier_Filter
{

    public $name = 'Linkify';

    protected $host_list = array();
    protected $config;


    protected function fnmatch_utf8($pattern,$str){
        return preg_match("#^".strtr(preg_quote($pattern, '#'), array('\*' => '.*', '\?' => '.'))."$#i", $str);
    }

    protected function is_safe($uri){
       $rel = false;

       foreach($this->host_list as $host){
           if ($this->fnmatch_utf8($host,$uri) || $this->fnmatch_utf8($host."/*",$uri)) { 
                $rel = true;
                break;
           }
       }


       return $rel;
    }


    public function process($search){
       $new_content = $this->autolink($search);
       return $new_content;
    }

    public function postFilter($html, $config, $context){
        //$content = strtolower($content);
        $this->host_list = $config->get('Linkify.Hostlist');
        $content = $html;
        $counter = 0;
        $tmp = "";
        $search = "";
        $size = strlen($content);
        for ($i = 0; $i < $size; $i++) {
            if ($content[$i] == "<" and $content[$i+1] == "a") {
                if ($search and $counter == 0) {
                    //$tmp .= str_replace($find, $replace, $search);
                    $tmp.= $this->process($search);
                    $search = "";
                }
                $counter += 2;
                $i += 1;
                $tmp .= "<a";
            } else if ($content[$i] == "<") {
                if ($search and $counter == 0) {
                    //$tmp .= str_replace($find, $replace, $search);
                    $tmp .= $this->process($search);
                    $search = "";
                }
                $counter += 1;
                $tmp .= "<";
            } else if ($content[$i] == "a" and $content[$i+1] == ">") {
                if ($search and $counter == 0) {
                    //$tmp .= str_replace($find, $replace, $search);
                    $tmp .= $this->process($search);
                    $search = "";
                }
                $counter -= 2;
                $i += 1;
                $tmp .= "a>";
            }else if ($content[$i] == ">") {
                if ($search and $counter == 0) {
                    //$tmp .= str_replace($find, $replace, $search);
                    $tmp .= $this->process($search);
                    $search = "";
                }
                $counter -= 1;
                $tmp .= ">";
            }else if ($counter == 0) {
                $search .= $content[$i];
            }else {
                $tmp .= $content[$i];
            }
        }

        if(trim($search)){
            $tmp .= $this->process($search);
        }
        return $tmp;

    }


    /**自动转为超链接*/

    function autolink($foo)
    {
        return preg_replace_callback("/(http[s]?:[^\s]*)/i", array($this,'url_replace'),$foo);
    }

    function url_replace($matches){
        if($this->is_safe($matches[0])){
            return "<a href=\"$matches[0]\">$matches[0]</a>";

        } else {
            return $matches[0];
        }
    }


}

// vim: et sw=4 sts=4
