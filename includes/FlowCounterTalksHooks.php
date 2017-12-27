<?php
use FlowCounterTalksHooks;

class Hook {

    public static function onSkinTemplateNavigation( &$page, &$content_navigation ) {
        global $wgUser;
  		var_dump($page->getTitle()->getText());
        var_dump($content_navigation);

    }
}