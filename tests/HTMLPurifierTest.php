<?php

class HTMLPurifierTest extends HTMLPurifier_Harness
{
    protected $purifier;

    function testNull() {
        $this->assertPurification("Null byte\0", "Null byte");
    }

    function test_purifyArray() {

        $this->assertIdentical(
            $this->purifier->purifyArray(
                array('Good', '<b>Sketchy', 'foo' => '<script>bad</script>')
            ),
            array('Good', '<b>Sketchy</b>', 'foo' => '')
        );

        $this->assertIsA($this->purifier->context, 'array');

    }

    function testGetInstance() {
        $purifier  = HTMLPurifier::getInstance();
        $purifier2 = HTMLPurifier::getInstance();
        $this->assertReference($purifier, $purifier2);
    }

    function testMakeAbsolute() {
        $this->config->set('URI.Base', 'http://example.com/bar/baz.php');
        $this->config->set('URI.MakeAbsolute', true);
        $this->assertPurification(
            '<a href="foo.txt">Foobar</a>',
            '<a href="http://example.com/bar/foo.txt">Foobar</a>'
        );
    }

    function testDisableResources() {
        $this->config->set('URI.DisableResources', true);
        $this->assertPurification('<img src="foo.jpg" />', '');
    }

    function test_addFilter_deprecated() {
        $this->expectError('HTMLPurifier->addFilter() is deprecated, use configuration directives in the Filter namespace or Filter.Custom');
        generate_mock_once('HTMLPurifier_Filter');
        $this->purifier->addFilter($mock = new HTMLPurifier_FilterMock());
        $mock->expectOnce('preFilter');
        $mock->expectOnce('postFilter');
        $this->purifier->purify('foo');
    }

    function test_hostwhitelist(){
        $this->config->set('URI.HostWhitelist',array('www.taobao.com','img01.daily.taobaocdn.net'));
        $uri = $this->config->getDefinition('URI');
        $uri->addFilter(new HTMLPurifier_URIFilter_HostWhitelist(),$this->config);
        $this->assertPurification('<img src="foo.jpg" />', '');

        $this->assertPurification('<img src="http://www.taobao.com/foo.jpg" />', '<img src="http://www.taobao.com/foo.jpg" alt="foo.jpg" />');

        $this->assertPurification('<a href="http://www.taobao.com/foo.jpg">test</a>', '<a href="http://www.taobao.com/foo.jpg">test</a>');

        $this->assertPurification('<a href="http://www.sina.com/foo.jpg">test</a>', '<a>test</a>');

    }

    function test_flashhostwhitelist(){
        $this->config->set('HTML.SafeEmbed', true);
        $this->config->set('Output.FlashCompat', true);
        $this->config->set('HTML.FlashAllowFullScreen', true);//允许全屏
        $this->config->set('URI.FlashHostWhitelist',array('*.taobao.com','*.daily.taobaocdn.net'));
        $this->config->set('Filter.Custom',array(new HTMLPurifier_Filter_FlashObject()));
        $this->config->set('Cache.DefinitionImpl',NULL);
        $hm = $this->config->getHTMLDefinition(true);
        $hm->manager->addModule(new HTMLPurifier_HTMLModule_SafeFlashObject());
        //$this->config->set('AutoFormat.FlashHostWhitelist', true);

        $uri = $this->config->getDefinition('URI');
        $uri->addFilter(new HTMLPurifier_URIFilter_FlashHostWhitelist(),$this->config);

        $content = "<object><param name='video' value='http://www.a.com' /></object>";

        $this->assertPurification($content, '<object type="application/x-shockwave-flash"><param name="allowFullScreen" value="true" /><param name="allowNetworking" value="internal" /><param name="allowScriptAccess" value="never" /><param name="" value="" /></object>');

        $content = "<object><param name='movie' value='http://www.taobao.com/a.swf' /></object>";

        $this->assertPurification($content, '<object type="application/x-shockwave-flash"><param name="allowFullScreen" value="true" /><param name="allowNetworking" value="internal" /><param name="allowScriptAccess" value="never" /><param name="movie" value="http://www.taobao.com/a.swf" /></object>');

        //param name wrong
        $content = "<object><param name='video' value='http://www.taobao.com/a.swf' /></object>";
        $this->assertpurification($content, '<object type="application/x-shockwave-flash"><param name="allowFullScreen" value="true" /><param name="allowNetworking" value="internal" /><param name="allowScriptAccess" value="never" /><param name="" value="" /></object>');

        $content = "<object data='http://www.a.com/a.swf'><param name='movie' value='http://www.taobao.com/a.swf' /></object>";

        $this->assertPurification($content, '<object type="application/x-shockwave-flash"><param name="allowFullScreen" value="true" /><param name="allowNetworking" value="internal" /><param name="allowScriptAccess" value="never" /><param name="movie" value="http://www.taobao.com/a.swf" /></object>');

        $content = "<object data='http://www.taobao.com/a.swf'><param name='movie' value='http://www.b.com/a.swf' /></object>";

        $this->assertPurification($content, '<object data="http://www.taobao.com/a.swf" type="application/x-shockwave-flash"><param name="allowFullScreen" value="true" /><param name="allowNetworking" value="internal" /><param name="allowScriptAccess" value="never" /><param name="movie" value="" /></object>');
        
        $content = "<object data='http://www.taobao.com/a.swf'></object>";
        
        $this->assertPurification($content, '<object data="http://www.taobao.com/a.swf" type="application/x-shockwave-flash"><param name="allowFullScreen" value="true" /><param name="allowNetworking" value="internal" /><param name="allowScriptAccess" value="never" /></object>');

    }

    function test_linkify(){
        $this->config->set('HTML.Doctype', 'XHTML 1.0 Transitional'); // replace with your
        $this->config->set('HTML.SafeEmbed', true);
        /*not recommend to use this Module, because it will enable the SafeObject Inector Module, and the Inector Module is uneffiency */
        //$this->config->set('AutoFormat.Linkify',true);
        //$this->config->set('HTML.SafeObject', true);
        //$this->config->set('Output.FlashCompat', true);
        $this->config->set('HTML.FlashAllowFullScreen', true);//允许全屏
        $this->config->set('HTML.Allowed', 'object[data],param[name|value],embed[src|type|allowscriptaccess|width|height],a[href|title|id],div[style|id|class],img[src|alt|title],h2[id],h3,h4,b,strong,i,em,u,ul,ol,li,p[style],br,span[style]');
        $this->config->set('Cache.DefinitionImpl',NULL);
        $this->config->set('URI.HostWhitelist',array('*.taobao.com','*.daily.taobaocdn.net'));
        $this->config->set('URI.FlashHostWhitelist',array('www.b.com','www.a.com','www.x.com','*.ku6.com'));
//        $this->config->set('Filter.YouTube', true);

        /* use the custom FlashObject Filter to instead of the SafeObject Inector */
        $this->config->set('Linkify.Hostlist',array('http://*.taobao.com'));
        $this->config->set('Filter.Custom',array(new HTMLPurifier_Filter_Linkify(),new HTMLPurifier_Filter_FlashObject()));

        /* use custom Whitelist URIFilter to Filter the unsafe URL */
        $uri = $this->config->getDefinition('URI');
        $uri->addFilter(new HTMLPurifier_URIFilter_HostWhitelist(),$this->config);
        $uri->addFilter(new HTMLPurifier_URIFilter_FlashHostWhitelist(),$this->config);
        /* use the custom safeflash object module to ingore the inector module */
        $hm = $this->config->getHTMLDefinition(true);
        $hm->manager->addModule(new HTMLPurifier_HTMLModule_SafeFlashObject());


        $content = "<span>http://www.taobao.com</span>";

        $after = $this->purifier->purify($content,$this->config);

        print_r($after);
        
 
    }

    function test_performace(){
//        ini_set('memory_limit', '128M');
        $this->config->set('HTML.Doctype', 'XHTML 1.0 Transitional'); // replace with your
        $this->config->set('HTML.SafeEmbed', true);
        /*not recommend to use this Module, because it will enable the SafeObject Inector Module, and the Inector Module is uneffiency */
        //$this->config->set('AutoFormat.Linkify',true);
        //$this->config->set('HTML.SafeObject', true);
        //$this->config->set('Output.FlashCompat', true);
        $this->config->set('HTML.FlashAllowFullScreen', true);//允许全屏
        $this->config->set('HTML.Allowed', 'object[data],param[name|value],embed[src|type|allowscriptaccess|width|height],a[href|title|id],div[style|id|class],img[src|alt|title],h2[id],h3,h4,b,strong,i,em,u,ul,ol,li,p[style],br,span[style]');
        $this->config->set('Cache.DefinitionImpl',NULL);
        $this->config->set('URI.HostWhitelist',array('*.taobao.com','*.daily.taobaocdn.net'));
        $this->config->set('URI.FlashHostWhitelist',array('www.b.com','www.a.com','www.x.com','*.ku6.com'));
//        $this->config->set('Filter.YouTube', true);

        /* use the custom FlashObject Filter to instead of the SafeObject Inector */
        $this->config->set('Filter.Custom',array(new HTMLPurifier_Filter_FlashObject(),new HTMLPurifier_Filter_Linkify()));

        /* use custom Whitelist URIFilter to Filter the unsafe URL */
        $uri = $this->config->getDefinition('URI');
        $uri->addFilter(new HTMLPurifier_URIFilter_HostWhitelist(),$this->config);
        $uri->addFilter(new HTMLPurifier_URIFilter_FlashHostWhitelist(),$this->config);
        /* use the custom safeflash object module to ingore the inector module */
        $hm = $this->config->getHTMLDefinition(true);
        $hm->manager->addModule(new HTMLPurifier_HTMLModule_SafeFlashObject());


        $content = file_get_contents("3086.html");
        $t1 = microtime(true);
        //$content = "<div><div><a href='xxx'>fff</a></div></div><object data='http://www.x.com/a.swf'><param name='movie' value='http://www.aa.com' /><param name='allowFullScreen    ' value='false'></param></object><object data='http://www.xx.com/a.swf'><param name='movie' value='http://www.a.com' /></param></object>";
        //$content = "<a href='http://www.taobao.com'>fff</a>";
        //$content = "<object data='http://www.a.com/a.swf'></object>";
        //$content = '<embed height="400" width="480" allowscriptaccess="never" pluginspage="http://get.adobe.com/cn/flashplayer/" flashvars="playMovie=true&amp;amp;auto=1" allowfullscreen="true" quality="high" type="application/x-shockwave-flash" wmode="transparent" style="display:inline-block" src="http://player.ku6.com/refer/TsxxCy_rk4Kt_xVZ/v.swf">';
        $after = $this->purifier->purify($content,$this->config);
        $t2 = microtime(true);
        echo (($t2-$t1)*1000)."ms\n";
        //echo $after;


    }

    function test_special_chars(){
        $this->config->set('Core.Encoding', 'gbk');
        $this->config->set('Core.EscapeSpecialCharacters',true);
        $this->config->set('HTML.Doctype', 'XHTML 1.0 Transitional'); 
        $this->config->set('Core.SpecialCharacters',array(8226));
        $content = "&#8226;";
        $after = $this->purifier->purify($content,$this->config);
        
        $this->assertEqual($content,$after);
	
    }

    function test_injector_performace(){
//        ini_set('memory_limit', '128M');
        $this->config->set('HTML.Doctype', 'XHTML 1.0 Transitional'); // replace with your
        $this->config->set('HTML.SafeEmbed', true);
        /*not recommend to use this Module, because it will enable the SafeObject Inector Module, and the Inector Module is uneffiency */
        $this->config->set('HTML.SafeObject', true);
        //$this->config->set('Output.FlashCompat', true);
        $this->config->set('HTML.FlashAllowFullScreen', true);//允许全屏
        $this->config->set('HTML.Allowed', 'object[data],param[name|value],embed[src|type|allowscriptaccess|width|height],a[href|title|id],div[style|id|class],img[src|alt|title],h2[id],h3,h4,b,strong,i,em,u,ul,ol,li,p[style],br,span[style]');
        $this->config->set('Cache.DefinitionImpl',NULL);
        $this->config->set('URI.HostWhitelist',array('*.taobao.com','*.daily.taobaocdn.net'));
        $this->config->set('URI.FlashHostWhitelist',array('www.b.com','www.a.com','www.x.com','*.ku6.com'));
//        $this->config->set('Filter.YouTube', true);

        /* use the custom FlashObject Filter to instead of the SafeObject Inector */
        //$this->config->set('Filter.Custom',array(new HTMLPurifier_Filter_FlashObject()));

        /* use custom Whitelist URIFilter to Filter the unsafe URL */
        $uri = $this->config->getDefinition('URI');
        $uri->addFilter(new HTMLPurifier_URIFilter_HostWhitelist(),$this->config);
        $uri->addFilter(new HTMLPurifier_URIFilter_FlashHostWhitelist(),$this->config);
        /* use the custom safeflash object module to ingore the inector module */
        //$hm = $this->config->getHTMLDefinition(true);
        //$hm->manager->addModule(new HTMLPurifier_HTMLModule_SafeFlashObject());

        $content = file_get_contents("3086.html");
        $t1 = microtime(true);
        //$content = "<div><div><a href='xxx'>fff</a></div></div><object data='http://www.x.com/a.swf'><param name='movie' value='http://www.aa.com' /><param name='allowFullScreen    ' value='false'></param></object><object data='http://www.xx.com/a.swf'><param name='movie' value='http://www.a.com' /></param></object>";
        //$content = "<a href='http://www.taobao.com'>fff</a>";
        //$content = "<object data='http://www.a.com/a.swf'><param name='flashvars' value='xxx'/></object>";
        //$content = '<embed height="400" width="480" allowscriptaccess="never" pluginspage="http://get.adobe.com/cn/flashplayer/" flashvars="playMovie=true&amp;amp;auto=1" allowfullscreen="true" quality="high" type="application/x-shockwave-flash" wmode="transparent" style="display:inline-block" src="http://player.ku6.com/refer/TsxxCy_rk4Kt_xVZ/v.swf">';
        $after = $this->purifier->purify($content,$this->config);
        $t2 = microtime(true);
        echo (($t2-$t1)*1000)."ms\n";
        //echo $after;


    }


}

// vim: et sw=4 sts=4
