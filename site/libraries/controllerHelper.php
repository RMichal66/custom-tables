<?php
/**
 * CustomTables Joomla! 3.x/4.x/5.x Component
 * @package Custom Tables
 * @author Ivan Komlev <support@joomlaboat.com>
 * @link https://joomlaboat.com
 * @copyright (C) 2018-2025. Ivan Komlev
 * @license GNU/GPL Version 2 or later - https://www.gnu.org/licenses/gpl-2.0.html
 **/

// no direct access
defined('_JEXEC') or die();

use CustomTables\common;
use CustomTables\CT;
use CustomTables\CTMiscHelper;
use CustomTables\Layouts;

class controllerHelper
{
	public static function doTheTask(string $task)
	{
		$link = common::getReturnToURL() ?? '';

		$ct = new CT(null, false);
		$ct->Params->constructJoomlaParams();
		$layout = new Layouts($ct);

		$result = $layout->renderMixedLayout($ct->Params->editLayout, null, $task);

		if ($result['success']) {
			if ($ct->Env->clean) {
				if ($ct->Env->frmt == 'json')
					CTMiscHelper::fireSuccess($result['id'] ?? null, $result['data'] ?? null, $ct->Params->msgItemIsSaved);
				else
					die($result['short'] ?? $task);
			}

			if (isset($result['redirect']))
				$link = $result['redirect'];

			//This is to redirect to new record, if returnto contains $get_listing_id value
			$link = str_replace('$get_listing_id', common::inputGet("listing_id", 0, 'INT'), $link);

			return ['link' => $link, 'message' => $result['message'], 'success' => true];
		} else {
			if ($ct->Env->clean) {
				if ($ct->Env->frmt == 'json')
					CTMiscHelper::fireError(500, $result['message'] ?? 'Error');
				else
					die($result['short'] ?? 'error');
			}

			if (isset($result['redirect']))
				$link = $result['redirect'];

			if (isset($result['captcha']) and $result['captcha']) {
				$content = '
<script>
setTimeout("history.go(-1)", 2000);
</script>
';
				return ['link' => $link, 'message' => common::translate('COM_CUSTOMTABLES_INCORRECT_CAPTCHA'), 'success' => false, 'html' => $content];
			} else {
				return ['link' => $link, 'message' => $result['message'], 'success' => false];
			}
		}
	}
}