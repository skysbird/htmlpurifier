<?php

class HTMLPurifier_Filter_Linkify extends HTMLPurifier_Filter
{

    public $name = 'Linkify';

    protected $host_list = array();
    protected $config;


    protected function is_safe($uri){
       $rel = false;
       foreach($this->host_list as $host){
           if (fnmatch($host,$uri) || fnmatch($host."/*",$uri)) { 
                $rel = true;
                break;
           }
       }

       return $rel;
    }


    protected function wrapHtml($html){
        $ret = '<html><head>';
        $ret .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        // No protection if $html contains a stray </div>!
        $ret .= '</head><body>'.$html.'</body></html>';
        return $ret;
    }

    public function postFilter($html, $config, $context) {
        if(trim($html)){
            $html = $this->wrapHtml($html);
            $this->host_list = $config->get('Linkify.Hostlist');
            $this->config = $config;
            $doc = new DOMDocument();
            $doc->encoding = "UTF-8";
            $doc->loadHTML($html);  
            $xpath = new DOMXPath($doc);
            $textnodes = $xpath->query('//text()[not(ancestor::a) and normalize-space()]');
            
            $temp_doc = new DOMDocument();
            $temp_doc->encoding = "UTF-8";

            
            foreach($textnodes as $node){
                $v = $node->nodeValue;
                if(trim($v)){ 
                    $parent = $node->parentNode;
                    //$parent->removeChild($node);
                    //print_r($node);
                    $new_content = $this->autolink($v);
                    //echo $new_content."\n";
                    
                    $new_content = $this->wrapHtml($new_content);
                    $temp_doc->loadHTML($new_content);  
                    $new_content_node_list = $temp_doc->getElementsByTagName('body')->item(0)->childNodes;
                    $replaced = False;

                    $len = $new_content_node_list->length;
                    $last_insert_node = NULL;
                    while($len--){
                       $new_content_node = $new_content_node_list->item($len);
                       $new_content_node = $doc->importNode($new_content_node,true);
                       if(!$replaced){
                         $parent->replaceChild($new_content_node,$node);
                         $replaced = True;
                       } else {
                         $parent->insertBefore($new_content_node,$last_insert_node);
                       }

                       $last_insert_node = $new_content_node;
                       //echo $doc->saveXML($parent)."\n";
 
                    }

                    /*foreach($new_content_node_list as $new_content_node){
                          print_r($new_content_node);
                          $new_content_node = $doc->importNode($new_content_node,true);
                          if(!$replaced){
                            $parent->replaceChild($new_content_node,$node);
                            $replaced = True;
                          } else {
                            $parent->appendChild($new_content_node);
                          }
                          echo $doc->saveXML($parent)."\n";
                    }*/
                }
            }
            $b = $doc->getElementsByTagName('body')->item(0);
            return preg_replace('~<(?:!DOCTYPE|/?(?:html|head|body))[^>]*>\s*~i', '', $doc->saveXML($b));

        } else {
            return "";
        }
    }

    /**自动转为超链接*/

    function autolink($foo)
    {
    // Modified from:  http://www.szcpost.com
        return preg_replace_callback("/(http[s]?:[^\s]*)/i", array($this,'url_replace'),$foo);
    }

    function url_replace($matches){
        if($this->is_safe($matches[0])){
            return "<a href='$matches[0]'>$matches[0]</a>";

        } else {
            return $matches[0];
        }
    }


}

// vim: et sw=4 sts=4
