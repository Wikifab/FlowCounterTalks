<?php
namespace FlowCounterTalks;
use FauxRequest;
use ApiMain;
use Flow\Container;
use SMW\ContentParser;
use Title;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMWDINumber;
use RuntimeException;

class Hook {

	public static function onAPIFlowAfterExecute($module) {

		//TODO doesn't seem to work

		$title = $module->getTitle();
		
		if ( !$title instanceof Title ) {
			throw new RuntimeException( 'Expected a title instance' );
		}

		$job = new UpdateJob( $title );
		$job->run();
	}

	public static function onExtension() {

		global $sespSpecialProperties, $sespLocalPropertyDefinitions;

		//add property annotator to SESP
		$sespSpecialProperties[] = '_FLOW_COUNTER_TALKS';

		$sespLocalPropertyDefinitions['_FLOW_COUNTER_TALKS'] = [
		    'id'    => '___FLOW_COUNTER_TALKS',
		    'type'  => '_num',
		    'alias' => 'flowcountertalks-talkscounter-prop',
		    'label' => 'Talks Counter',
		    'callback'  => function( $appFactory, $property, $semanticData ){

		    	$talksCounter = self::getFlowCount( $semanticData->getSubject()->getTitle()->getDBkey());

		    	return new SMWDINumber($talksCounter);
		    }
		];
	}

	public static function onSkinTemplateNavigation( &$content_navigation, array &$links  ) {

		global $wgContLang, $wgUser, $wgTitle;

		$title = $wgTitle->getDBkey();

		if ( ! isset($links['namespaces']['talk']) && !isset ($links['namespaces']['form_talk'])) {
			// if there isn't talk tab, no need te get counter
			return;
		}

		$counterTalk = self::getFlowCount($title);
		//$counterTalk = self::getFlowCountUsingApi($title);

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

	/**
	 * this function do an api call with a FauxRequest
	 * but it doesn't manage erros correctly because of "die()" calls in the api in Flow
	 *
	 * @deprecated
	 * @param unknown $titleText
	 */
	private static function getFlowCountUsingApi($titleText) {
		$wgEnableWriteAPI = true;
		$params = new FauxRequest(array (
				'action' => 'flow',
				'submodule'=> 'view-topiclist',
				'page' => 'Discussion:'.$titleText,
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
			trigger_error("Exception in flow api", E_USER_WARNING);
			$counterTalk = 0;
		}
		return $counterTalk;
	}

	private static function getFlowCount($titletext) {

		$dbw = wfGetDB( DB_MASTER );
		$dbr = Container::get( 'db.factory' )->getDB( DB_SLAVE );
		/** @var \Flow\LinksTableUpdater $linksTableUpdater */
		$linksTableUpdater = Container::get( 'reference.updater.links-tables' );

		$iterator = new \BatchRowIterator( $dbr, 'flow_workflow', 'workflow_id', $batchSize = 99 );
		$iterator->setFetchColumns( array( '*' ) );
		$iterator->addConditions( array(
				'workflow_wiki' => wfWikiID(),
				'workflow_title_text' => $titletext
		) );

		$count = 0;
		foreach ( $iterator as $rows ) {
			foreach ( $rows as $row ) {
				$workflow = \Flow\Model\Workflow::fromStorageRow( (array) $row );

				$iteratorT = new \BatchRowIterator( $dbr, 'flow_revision', 'rev_id', $batchSize = 99 );
				$iteratorT->setFetchColumns( array( '*' ) );
				$iteratorT->addConditions( array(
						'rev_type_id' => $row->workflow_id
				) );
				foreach ($iteratorT as $rowsT) {
					$count += 1;
				}
			}
		}
		// improvement : we could do mysql count instead of counting in foreach
		// improvement : we could count only unread discussions

		return $count;

	}
}
