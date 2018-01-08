<?php
namespace FlowCounterTalks;
use FauxRequest;
use ApiMain;

class Hook {

    public static function onSkinTemplateNavigation( &$page, &$content_navigation ) {

        global $wgContLang, $wgUser;
        $mediaString = strtolower( $wgContLang->getNsText( NS_FILE ) );
        //$title = $mediaString . ':' . $filename;


        $pageTitle = $page ->getTitle();
        $title = $pageTitle -> getFullText();

       //$text = "\n[[" . $category . "]]";
        $wgEnableWriteAPI = true;
        $params = new FauxRequest(array (
            'action' => 'flow',
            'submodule'=> 'view-topiclist',
            'page' => 'Discussion:' .  $title,
        ), true, $_SESSION );
//         var_dump($params);
//         die();

        $enableWrite = true;
        try {
            $api = new ApiMain( $params, $enableWrite );
            $api->execute();
            if ( defined( 'ApiResult::META_CONTENT' ) ) {
                $data = $api->getResult()->getResultData();
            } else {
                $data = &$api->getResultData();
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

        return $mediaString;


    }
}
