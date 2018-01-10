<?php
namespace FlowCounterTalks;
use FauxRequest;
use ApiMain;

class Hook {

    public static function onSkinTemplateNavigation( &$content_navigation, array &$links  ) {

        global $wgContLang, $wgUser, $wgTitle;

        $title = $wgTitle->getText();

        $wgEnableWriteAPI = true;
        $params = new FauxRequest(array (
            'action' => 'flow',
            'submodule'=> 'view-topiclist',
            'page' => 'Discussion:' . $title,
        ), true, $_SESSION );


        $enableWrite = true;
        $api = new ApiMain( $params, $enableWrite );
        $api->execute();
        if ( defined( 'ApiResult::META_CONTENT' ) ) {
            $data = $api->getResult()->getResultData();

        } else {
            $data = &$api->getResultData();
        }
        $counterTalk = count($data['flow']['view-topiclist']['result']['topiclist']['roots']);
        $links['namespaces']['talk']['count']=$counterTalk;

    }

    public static function onBeforePageDisplay( \OutputPage &$out, \Skin &$skin ) {
        $out->addModuleStyles('ext.flowcountertalks.css');

    }
}
