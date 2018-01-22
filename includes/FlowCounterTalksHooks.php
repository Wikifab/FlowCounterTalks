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
            'page' => 'Discussion:'.$title,
        ), true, null );

        $enableWrite = true;
        try {
	        $api = new ApiMain( $params, $enableWrite );
	        $api->execute();
	        if ( defined( 'ApiResult::META_CONTENT' ) ) {
	            $data = $api->getResult()->getResultData();

	        } else {
	            $data = &$api->getResultData();
	        }
	        $counterTalk = count($data['flow']['view-topiclist']['result']['topiclist']['roots']);
        } catch (Exception $e) {
        	trigger_error("Exception in flow api", E_USER_NOTICE);
        	$counterTalk = 0;
        }

        // Si on est sur une page où il y a déjà un "talk" on met juste le compteur
        if (isset($links['namespaces']['talk'])){
            $links['namespaces']['talk']['count'] = $counterTalk;
        }
        // Sinon on met le compteur de "form-talk" à jour avec le compteur
        if (isset ($links['namespaces']['form_talk'])){
              $links['namespaces']['form_talk']['count'] = $counterTalk;

        }

    }

    public static function onBeforePageDisplay( \OutputPage &$out, \Skin &$skin ) {
        $out->addModuleStyles('ext.flowcountertalks.css');

    }
}
